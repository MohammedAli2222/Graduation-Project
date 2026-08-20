<?php

namespace App\Services;

use App\Enums\DiagnosisStatus;
use App\Enums\PatientStatus;
use App\Jobs\ProcessPatientImagesJob;
use App\Models\CaseType;
use App\Models\Patient;
use App\Repositories\PatientRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

    public function registerPatient(array $data, array $files = [], ?array $diagnosisData = null)
    {
        $user = auth()->user();
        $isReceptionist = $user->hasRole('receptionist');

        $tempImages = $isReceptionist ? $this->stageReceptionistImages($files) : [];

        if (! empty($files['id_card'])) {
            $tempImages['id_cards'] = $files['id_card']->store('temp/patients', 'local');
        }

        try {
            $patient = $this->persistPatient($data, $files, $diagnosisData, $user);
        } catch (\Throwable $e) {
            // فشل الحفظ ⇒ لا مالك للملفات المؤقتة، فننظّفها فوراً بدل تركها.
            $this->discardTempImages($tempImages);

            throw $e;
        }

        if (! empty($tempImages)) {
            // الإرسال بعد نجاح المعاملة فقط (المعاملة انتهت هنا فعلياً)،
            // حتى لا يبحث الـ Job عن مريض غير موجود.
            ProcessPatientImagesJob::dispatch($patient->id, $tempImages);
        }

        // مسار الاستقبال لا يُنشئ تشخيصات، وصور المريض تُعالَج في الخلفية،
        // فتحميل العلاقتين هنا استعلامات بلا نتيجة تُذكر.
        return $isReceptionist
            ? $patient->load('medicalHistory')
            : $patient->load(['medicalHistory', 'diagnoses.media', 'media']);
    }

    /**
     * تخزين مؤقت سريع لصور الاستقبال على قرص local (نقل بلا معالجة).
     *
     * @param  array<string, mixed>  $files
     * @return array<string, array<int, string>>
     */
    private function stageReceptionistImages(array $files): array
    {
        $tempImages = [];

        foreach (['clinical_images', 'x_ray_images'] as $collection) {
            if (empty($files[$collection])) {
                continue;
            }

            foreach ($files[$collection] as $imageOrGroup) {
                if (! $imageOrGroup) {
                    continue;
                }

                // تسطيح المصفوفات المتداخلة كما كانت MediaService::upload
                // تفعل ضمنياً، لأن الحقل قد يصل كمصفوفة صور لكل عنصر.
                foreach ((is_array($imageOrGroup) ? $imageOrGroup : [$imageOrGroup]) as $image) {
                    if ($image) {
                        $tempImages[$collection][] = $image->store('temp/patients', 'local');
                    }
                }
            }
        }

        return $tempImages;
    }

    /**
     * @param  array<string, array<int, string>|string>  $tempImages
     */
    private function discardTempImages(array $tempImages): void
    {
        foreach ($tempImages as $paths) {
            foreach ((array) $paths as $path) {
                Storage::disk('local')->delete($path);
            }
        }
    }


    private function persistPatient(array $data, array $files, ?array $diagnosisData, $user)
    {
        return DB::transaction(function () use ($data, $files, $diagnosisData, $user) {

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

            if ($user->hasRole('student') && ! empty($diagnosisData['case_type_ids'])) {
                $this->createStudentSuggestedDiagnoses($patient, $files, $diagnosisData, $user);
            }

            return $patient;
        });
    }

    /**
     * إنشاء التشخيصات التي يقترحها الطالب عند تسجيل مريض جديد.
     *
     * @param  array<string, mixed>  $files
     * @param  array<string, mixed>  $diagnosisData
     */
    private function createStudentSuggestedDiagnoses($patient, array $files, array $diagnosisData, $user): void
    {
        $student = $user->studentProfile;

        if (! $student) {
            throw new \Exception('Student academic profile not found.', 404);
        }

        $studentYear = (int) $student->academic_year;
        $studentSemester = (int) $student->semester;

        // تحميل أنواع الحالات دفعة واحدة بدل findOrFail داخل الحلقة (N+1).
        $caseTypes = CaseType::with('course')
            ->whereIn('id', $diagnosisData['case_type_ids'])
            ->get()
            ->keyBy('id');

        foreach ($diagnosisData['case_type_ids'] as $index => $caseTypeId) {
            $caseType = $caseTypes->get($caseTypeId);

            if (! $caseType || ! $caseType->course) {
                throw new \Exception("Case type #{$caseTypeId} not found.", 404);
            }

            $course = $caseType->course;

            $isAllowed = ($course->year < $studentYear)
                || ($course->year == $studentYear && $course->semester <= $studentSemester);

            if (! $isAllowed) {
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

            if (in_array($patient->availability_status, [PatientStatus::COMPLETED, PatientStatus::FULLY_RESERVED], true)) {
                $patient->update(['availability_status' => PatientStatus::WAITING_DIAGNOSIS->value]);
            }

            return $patient->load(['medicalHistory', 'diagnoses.media', 'media']);
        });
    }

    public function searchPatients(string $term): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->repository->search($term);
    }

    public function getPatientProfile(int $id)
    {
        return $this->repository->findWithMedia($id);
    }

    public function getReceptionistWaitingPatients(string $status = 'all')
    {
        return $this->repository->getReceptionistWaitingList($status);
    }

    /**
     * Endpoint A — المرضى بانتظار خطة تشخيص من المعيد (استقبال + اقتراح طلاب).
     *
     * @param  'all'|'reception'|'student'  $source
     */
    public function getPendingDiagnosisPatients(
        int $instructorProfileId,
        string $source = 'all',
        int $perPage = 10
    ): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
        $source = in_array($source, ['all', 'reception', 'student'], true) ? $source : 'all';

        return $this->repository->getPendingDiagnosisPatients($instructorProfileId, $source, $perPage);
    }

    /**
     * @deprecated استُبدلت بـ getPendingDiagnosisPatients(..., 'student').
     */
    public function getStudentPendingPatients(int $instructorProfileId)
    {
        return $this->repository->getPendingDiagnosisPatients($instructorProfileId, 'student');
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

    /**
     * زيارة جديدة لمريض موجود مسبقاً: تعيده لطابور "بانتظار التشخيص" دون
     * إنشاء سجل مريض مكرر، مع تحديث الشكوى الحالية والتاريخ الطبي بالكامل.
     *
     * تخزين الصور المؤقت يسبق المعاملة، والإرسال للـ Job يليها بعد نجاحها
     * تماماً كما في registerPatient، حتى لا يبحث الـ Job عن بيانات لم تُحفظ بعد.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $files
     */
    public function startNewVisit(int $patientId, array $data, array $files = []): Patient
    {
        $tempImages = $this->stageReceptionistImages($files);

        try {
            $patient = DB::transaction(function () use ($patientId, $data) {
                $patient = $this->repository->FindOrFail($patientId);

                $patient->update([
                    'preliminary_diagnosis' => $data['preliminary_diagnosis'],
                    'availability_status' => PatientStatus::WAITING_DIAGNOSIS->value,
                ]);

                $this->updateMedicalHistory($patient, $data);

                return $patient;
            });
        } catch (\Throwable $e) {
            $this->discardTempImages($tempImages);

            throw $e;
        }

        if (! empty($tempImages)) {
            ProcessPatientImagesJob::dispatch($patient->id, $tempImages);
        }

        return $patient->load('medicalHistory');
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

    public function getPatientDiagnoses(int $patientId, $studentId)
    {
        if (!Patient::where('id', $patientId)->exists()) {
            throw new \Exception('Patient not found.', 404);
        }

        return $this->repository->getAvailableDiagnosesForPatient($patientId, $studentId);
    }
}
