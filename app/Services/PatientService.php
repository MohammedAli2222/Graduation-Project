<?php

namespace App\Services;

use App\Enums\DiagnosisStatus;
use App\Enums\PatientStatus;
use App\Jobs\ProcessPatientImagesJob;
use App\Models\CaseType;
use App\Models\Patient;
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

            $patientData = [
                'full_name'    => $data['full_name'],
                'gender'       => $data['gender'],
                'phone'        => $data['phone'],
                'birth_date'   => $data['birth_date'],
                'address'      => $data['address'],
                'preliminary_diagnosis' => $data['preliminary_diagnosis'] ?? null,
                'added_by'     => auth()->id(),
                'availability_status' => PatientStatus::WAITING_DIAGNOSIS->value,
            ];

            $patient = $this->repository->create($patientData);

            $patient->medicalHistory()->create([
                'has_general_diseases'     => $data['has_general_diseases'],
                'general_diseases_details' => $data['general_diseases_details'] ?? null,
                'is_special_needs'         => $data['is_special_needs'],
                'special_needs_details'    => $data['special_needs_details'] ?? null,
                'takes_medications'        => $data['takes_medications'],
                'medications_details'      => $data['medications_details'] ?? null,
                'has_allergies'            => $data['has_allergies'],
                'allergies_details'        => $data['allergies_details'] ?? null,
                'is_pregnant'              => $data['gender'] === 'female' ? filter_var($data['is_pregnant'] ?? false, FILTER_VALIDATE_BOOLEAN) : null,
            ]);

            $user = auth()->user();

            // تخزين سريع فقط للملفات الخام على قرص local المؤقت (نقل بلا معالجة)،
            // بدل رفعها فوراً لمكتبة الوسائط داخل نفس الطلب. المعالجة الفعلية
            // (النقل لقرص public + توليد التحويلات + الربط بسجل المريض) تُنقل
            // بالكامل إلى ProcessPatientImagesJob لتستجيب شاشة الاستقبال فوراً
            $tempImages = [];

            if (! empty($files['id_card'])) {
                $tempImages['id_cards'] = $files['id_card']->store('temp/patients', 'local');
            }

            if ($user->hasRole('receptionist')) {
                foreach (['clinical_images', 'x_ray_images'] as $collection) {
                    if (empty($files[$collection])) {
                        continue;
                    }

                    foreach ($files[$collection] as $imageOrGroup) {
                        if (! $imageOrGroup) {
                            continue;
                        }

                        // نفس تسطيح المصفوفات المتداخلة الذي كانت MediaService::upload
                        // تقوم به ضمنياً، لأن clinical_images/x_ray_images قد تصل
                        // كمصفوفة صور لكل عنصر وليس صورة واحدة فقط
                        foreach ((is_array($imageOrGroup) ? $imageOrGroup : [$imageOrGroup]) as $image) {
                            if ($image) {
                                $tempImages[$collection][] = $image->store('temp/patients', 'local');
                            }
                        }
                    }
                }
            } elseif ($user->hasRole('student') && !empty($diagnosisData['case_type_ids'])) {
                $student = $user->studentProfile;

                foreach ($diagnosisData['case_type_ids'] as $index => $caseTypeId) {
                    $caseType = CaseType::with('course')->findOrFail($caseTypeId);
                    $course = $caseType->course;

                    $studentYear = (int) $student->academic_year;
                    $studentSemester = (int) $student->semester;
                    $isAllowed = ($course->year < $studentYear) || ($course->year == $studentYear && $course->semester <= $studentSemester);

                    if (!$isAllowed) {
                        throw new \Exception("Unauthorized: Cannot register for '{$caseType->name}'.", 403);
                    }

                    $diagnosis = $patient->diagnoses()->create([
                        'case_type_id' => $caseTypeId,
                        'department_id' => $course->department_id,
                        'suggested_by_student_id' => $user->id,
                        'status' => DiagnosisStatus::WAITING_APPROVAL->value,
                        'estimated_cost' => $diagnosisData['estimated_costs'][$index] ?? 0,
                    ]);

                    if (isset($files['clinical_images'][$index])) {
                        $this->mediaService->upload($diagnosis, $files['clinical_images'][$index], 'clinical_images');
                    }
                    if (isset($files['x_ray_images'][$index])) {
                        $this->mediaService->upload($diagnosis, $files['x_ray_images'][$index], 'x_ray_images');
                    }
                }
            }

            if (! empty($tempImages)) {
                // afterCommit(): نضمن أن الـ Job لا يبدأ قبل تأكيد حفظ سجل المريض
                // فعلياً في قاعدة البيانات (commit)، حتى لا يبحث عن مريض غير موجود بعد
                ProcessPatientImagesJob::dispatch($patient->id, $tempImages)->afterCommit();
            }

            return $patient->load(['medicalHistory', 'diagnoses.media', 'media']);
        });
    }

    public function addDiagnosesToExistingPatient(int $patientId, array $data, array $files = [])
    {
        return DB::transaction(function () use ($patientId, $data, $files) {

            $patient = $this->repository->FindOrFail($patientId);

            $user = auth()->user();
            $student = $user->studentProfile;

            if (! $student) {
                throw new \Exception('Student academic profile not found.', 404);
            }

            $studentYear = (int) $student->academic_year;
            $studentSemester = (int) $student->semester;

            $createdDiagnoses = [];

            foreach ($data['case_type_ids'] as $index => $caseTypeId) {
                $caseType = CaseType::with('course')->findOrFail($caseTypeId);
                $course = $caseType->course;

                $isAllowed = ($course->year < $studentYear) || ($course->year == $studentYear && $course->semester <= $studentSemester);

                if (! $isAllowed) {
                    throw new \Exception("Unauthorized: Cannot register for '{$caseType->name}'.", 403);
                }

                $diagnosis = $patient->diagnoses()->create([
                    'case_type_id' => $caseTypeId,
                    'department_id' => $course->department_id,
                    'suggested_by_student_id' => $user->id,
                    'status' => DiagnosisStatus::WAITING_APPROVAL->value,
                    'estimated_cost' => $data['estimated_costs'][$index] ?? 0,
                ]);

                if (isset($files['clinical_images'][$index])) {
                    $this->mediaService->upload($diagnosis, $files['clinical_images'][$index], 'clinical_images');
                }
                if (isset($files['x_ray_images'][$index])) {
                    $this->mediaService->upload($diagnosis, $files['x_ray_images'][$index], 'x_ray_images');
                }

                $createdDiagnoses[] = $diagnosis;
            }

            if (in_array($patient->availability_status, [PatientStatus::COMPLETED->value, PatientStatus::FULLY_RESERVED->value], true)) {
                $patient->update(['availability_status' => PatientStatus::WAITING_DIAGNOSIS->value]);
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

        if ($patient->gender === 'female' && array_key_exists('is_pregnant', $data)) {
            $medicalData['is_pregnant'] = filter_var($data['is_pregnant'], FILTER_VALIDATE_BOOLEAN);
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

    public function getPatientDiagnoses(int $patientId ,$studentId)
    {
        if (!Patient::where('id', $patientId)->exists()) {
            throw new \Exception('Patient not found.', 404);
        }

        return $this->repository->getAvailableDiagnosesForPatient($patientId ,$studentId);
    }
}
