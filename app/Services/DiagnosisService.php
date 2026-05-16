<?php

namespace App\Services;

use App\Repositories\DiagnosisRepository;
use App\Repositories\PatientRepository;
use App\Enums\DiagnosisStatus;
use App\Enums\PatientStatus;
use App\Models\CaseType;
use Illuminate\Support\Facades\DB;

class DiagnosisService
{
    public function __construct(
        protected DiagnosisRepository $diagnosisRepo,
        protected PatientRepository $patientRepo
    ) {}

    public function storeMultiple(array $data, int $instructorId)
    {
        return DB::transaction(function () use ($data, $instructorId) {
            $createdDiagnoses = [];

            foreach ($data['diagnoses'] as $item) {
                // جلب نوع الحالة لمعرفة القسم تلقائياً
                $caseType = CaseType::with('course')->findOrFail($item['case_type_id']);

                $createdDiagnoses[] = $this->diagnosisRepo->create([
                    'patient_id'    => $data['patient_id'],
                    'instructor_id' => $instructorId,
                    'case_type_id'  => $item['case_type_id'],
                    'department_id' => $caseType->course->department_id,
                    'final_diagnosis' => $item['final_diagnosis'],
                    'suggested_by_student_id' => $item['suggested_by_student_id'] ?? null,
                    'status'        => DiagnosisStatus::AVAILABLE->value,
                ]);
            }

            // تحديث حالة المريض ليصبح متاحاً للطلاب
            $this->patientRepo->updateAvailability($data['patient_id'], PatientStatus::AVAILABLE->value);

            return $createdDiagnoses;
        });
    }
}
