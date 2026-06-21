<?php

namespace App\Services;

use App\Enums\DiagnosisStatus;
use App\Enums\PatientStatus;
use App\Models\CaseType;
use App\Repositories\PatientRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatientService
{
    protected $repository;

    protected $mediaService;

    public function __construct(PatientRepository $repository, MediaService $mediaService)
    {
        $this->repository = $repository;
        $this->mediaService = $mediaService;
    }

    public function registerPatient(array $data, $images = null, $diagnosisData = null)
    {
        return DB::transaction(function () use ($data, $images, $diagnosisData) {
            $patientData = [
                'full_name' => $data['full_name'],
                'gender' => $data['gender'],
                'phone' => $data['phone'],
                'preliminary_diagnosis' => $data['preliminary_diagnosis'] ?? null,
            ];

            $patient = $this->repository->create($patientData);

            $patient->medicalHistory()->create([
                'has_general_diseases' => $data['has_general_diseases'],
                'general_diseases_details' => $data['general_diseases_details'] ?? null,
                'is_special_needs' => $data['is_special_needs'],
                'special_needs_details' => $data['special_needs_details'] ?? null,
                'takes_medications' => $data['takes_medications'],
                'medications_details' => $data['medications_details'] ?? null,
                'has_allergies' => $data['has_allergies'],
                'allergies_details' => $data['allergies_details'] ?? null,
            ]);

            if ($diagnosisData) {

                $caseType = CaseType::with('course')->findOrFail($diagnosisData['case_type_id']);
                $course = $caseType->course;

                if (! $course) {
                    throw new \Exception('The selected case type is not linked to any valid academic course.', 422);
                }

                $student = auth()->user()->studentProfile;
                if (! $student) {
                    throw new \Exception('Student profile not found for the authenticated user.', 404);
                }

                $studentYear = is_object($student->academic_year) ? (int) $student->academic_year->value : (int) $student->academic_year;
                $studentSemester = is_object($student->semester) ? (int) $student->semester->value : (int) $student->semester;

                $courseYear = (int) $course->year;
                $courseSemester = (int) $course->semester;

                if ($courseYear !== $studentYear || $courseSemester !== $studentSemester) {
                    throw new \Exception("Unauthorized: You can only register cases for courses in your current academic standing (Year: {$studentYear}, Semester: {$studentSemester}).", 403);
                }

                $patient->diagnoses()->create([
                    'case_type_id' => $diagnosisData['case_type_id'],
                    'department_id' => $course->department_id,
                    'suggested_by_student_id' => auth()->user()->id,
                    'status' => DiagnosisStatus::WAITING_APPROVAL->value,
                ]);
            }

            if ($images) {
                $this->mediaService->upload($patient, $images, 'patient_records');
            }

            return $patient->load(['medicalHistory', 'diagnoses', 'media']);
        });
    }

    public function searchPatients(string $term)
    {
        return $this->repository->search($term);
    }

    public function getPatientProfile(int $id)
    {
        return $this->repository->findWithMedia($id);
    }

    public function getReceptionistWaitingPatients()
    {
        return $this->repository->getReceptionistWaitingList();
    }

    public function getStudentPendingPatients(int $instructorProfileId)
    {
        return $this->repository->getStudentPendingRequests($instructorProfileId);
    }

    public function updatePatient(int $id, array $data, $images = null)
    {
        return DB::transaction(function () use ($id, $data, $images) {

            $patient = $this->repository->FindOrFail($id);

            $patient->update(collect($data)->only([
                'full_name',
                'gender',
                'phone',
                'preliminary_diagnosis',
            ])->toArray());

            $this->updateMedicalHistory($patient, $data);

            if ($images) {
                $patient->clearMediaCollection('patient_records');
                $this->mediaService->upload($patient, $images, 'patient_records');
            }

            return $patient->fresh('medicalHistory');
        });
    }

    public function getDailyDashboardStats(int $receptionistId)
    {
        return [

            'total_today' => $this->repository->getReceptionistStatsByStatus($receptionistId),

            'waiting_today' => $this->repository->getReceptionistStatsByStatus($receptionistId, PatientStatus::WAITING_DIAGNOSIS->value),

        ];
    }

    private function updateMedicalHistory($patient, array $data)
    {
        $map = [
            'has_general_diseases' => 'general_diseases_details',
            'is_special_needs' => 'special_needs_details',
            'takes_medications' => 'medications_details',
            'has_allergies' => 'allergies_details',
        ];

        $medicalData = [];

        foreach ($map as $boolField => $detailField) {
            if (array_key_exists($boolField, $data)) {
                $value = filter_var($data[$boolField], FILTER_VALIDATE_BOOLEAN);
                $medicalData[$boolField] = $value;

                $medicalData[$detailField] = $value ? ($data[$detailField] ?? null) : null;
            } elseif (array_key_exists($detailField, $data)) {
                $medicalData[$detailField] = $data[$detailField];
            }
        }

        if (! empty($medicalData)) {
            $patient->medicalHistory()->updateOrCreate(
                ['patient_id' => $patient->id],
                $medicalData
            );
        }
    }

    public function getAvailablePatientsByCaseType(int $caseTypeId)
    {
        $student = auth()->user()->loadMissing('studentProfile');
        $profile = $student->studentProfile;

        if (! $profile) {
            throw new \Exception('Student profile not found.', 404);
        }

        $studentId = auth()->user()->studentProfile->id;

        $isAllowed = $this->repository->checkIfCaseBelongsToActiveCourse($caseTypeId, $studentId);

        if (! $isAllowed) {
            throw new \Exception('Unauthorized access. This case type does not belong to your current academic year or semester.', 403);
        }

        $statusAvailable = DiagnosisStatus::AVAILABLE->value;

        return $this->repository->getByCaseTypeAndStatus($caseTypeId, $statusAvailable);
    }

    public function getDiagnosisDetails(int $id)
    {
        $student = auth()->user();
        $profile = $student->studentProfile;
        if (! $profile) {
            throw new \Exception('Student academic profile not found.', 404);
        }

        $diagnosis = $this->repository->getDiagnosisDetailsWithPatientMedia($id);

        $isAllowed = $this->repository->checkIfCaseBelongsToActiveCourse(
            $diagnosis->case_type_id,
            auth()->user()->studentProfile->id 
        );

        if (! $isAllowed) {
            throw ValidationException::withMessages([
                'case' => ['Access denied. This case does not belong to your current academic year or semester.'],
            ]);
        }

        return $diagnosis;
    }
}
