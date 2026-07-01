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

    public function registerPatient(array $data, array $files = [], array $diagnosisData = null)
    {
        return DB::transaction(function () use ($data, $files, $diagnosisData) {
            // 1. إنشاء المريض
            $patientData = [
                'full_name' => $data['full_name'],
                'gender'    => $data['gender'],
                'phone'     => $data['phone'],
                'birth_date' => $data['birth_date'],
                'address'   => $data['address'],
                'preliminary_diagnosis' => $data['preliminary_diagnosis'] ?? null,
                'added_by'  => auth()->id(),
            ];

            $patient = $this->repository->create($patientData);

            // 2. التاريخ الطبي
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

            // 3. معالجة التشخيصات والملفات المرتبطة بها
            if (!empty($diagnosisData['case_type_ids'])) {
                $student = auth()->user()->studentProfile;
                if (!$student) {
                    throw new \Exception('Student profile not found.', 404);
                }

                $studentYear = is_object($student->academic_year) ? (int) $student->academic_year->value : (int) $student->academic_year;
                $studentSemester = is_object($student->semester) ? (int) $student->semester->value : (int) $student->semester;

                foreach ($diagnosisData['case_type_ids'] as $index => $caseTypeId) {
                    $caseType = CaseType::with('course')->findOrFail($caseTypeId);
                    $course = $caseType->course;
                    
                    $isAllowed = ($course->year < $studentYear) ||
                        ($course->year == $studentYear && $course->semester <= $studentSemester);

                    if (!$isAllowed) {
                        throw new \Exception("Unauthorized: You cannot register for '{$caseType->name}' as it belongs to a future academic standing.", 403);
                    }

                    // إنشاء التشخيص
                    $diagnosis = $patient->diagnoses()->create([
                        'case_type_id' => $caseTypeId,
                        'department_id' => $course->department_id,
                        'suggested_by_student_id' => auth()->id(),
                        'status' => DiagnosisStatus::WAITING_APPROVAL->value,
                        'estimated_cost' => $diagnosisData['estimated_costs'][$index] ?? 0,
                    ]);

                    // رفع الصور الخاصة بهذا التشخيص فقط (داخل الـ foreach)
                    if (isset($files['clinical_images'][$index])) {
                        $this->mediaService->upload($diagnosis, $files['clinical_images'][$index], 'clinical_images');
                    }
                    if (isset($files['x_ray_images'][$index])) {
                        $this->mediaService->upload($diagnosis, $files['x_ray_images'][$index], 'x_ray_images');
                    }
                }
            }

            // 4. رفع صورة الهوية (للمريض)
            if (isset($files['id_card'])) {
                $this->mediaService->upload($patient, $files['id_card'], 'id_cards');
            }

            return $patient->load(['medicalHistory', 'diagnoses.media', 'media']);
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
