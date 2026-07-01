<?php

namespace App\Services;

use App\Enums\DiagnosisStatus;
use App\Enums\PatientStatus;
use App\Models\CaseType;
use App\Models\Group;
use App\Models\PatientDiagnose;
use App\Repositories\DiagnosisRepository;
use App\Repositories\PatientRepository;
use Exception;
use Illuminate\Support\Facades\DB;

class DiagnosisService
{
    public function __construct(
        protected DiagnosisRepository $diagnosisRepo,
        protected PatientRepository $patientRepo
    ) {}

    /**
     * تشخيص المرضى القادمين من موظف الاستقبال
     */
    public function storeMultiple(array $data, int $instructorId)
    {
        $hasPendingStudentDiagnosis = PatientDiagnose::where('patient_id', $data['patient_id'])
            ->where('status', DiagnosisStatus::WAITING_APPROVAL->value)
            ->exists();

        if ($hasPendingStudentDiagnosis) {
            throw new Exception('This patient has a pending diagnosis from a student. Please approve or reject it from the pending requests list.', 422);
        }

        if (! auth()->user()->instructorProfile?->id) {
            throw new Exception('Instructor profile not found.', 404);
        }

        return DB::transaction(function () use ($data, $instructorId) {
            $createdDiagnoses = [];

            foreach ($data['diagnoses'] as $item) {
                $caseType = CaseType::with('course')->findOrFail($item['case_type_id']);

                $createdDiagnoses[] = $this->diagnosisRepo->create([
                    'patient_id' => $data['patient_id'],
                    'instructor_id' => $instructorId,
                    'case_type_id' => $item['case_type_id'],
                    'department_id' => $caseType->course->department_id,
                    'final_diagnosis' => $item['final_diagnosis'],
                    'status' => DiagnosisStatus::AVAILABLE->value,
                ]);
            }

            $this->patientRepo->updateAvailability($data['patient_id'], PatientStatus::AVAILABLE->value);

            return $createdDiagnoses;
        });
    }

    public function approveCase(int $id, array $data, int $instructorId, int $instructorProfileId)
    {
        $diagnosis = $this->diagnosisRepo->FindOrFail($id);

        $this->validatePendingStatus($diagnosis);
        $this->authorizeInstructorForStudent($diagnosis, $instructorProfileId);

        DB::transaction(function () use ($diagnosis, $data, $instructorId) {
            $this->diagnosisRepo->update($diagnosis, [
                'status' => DiagnosisStatus::AVAILABLE->value,
                'instructor_id' => $instructorId,
                'final_diagnosis' => $data['final_diagnosis'],
            ]);

            $this->patientRepo->updateAvailability($diagnosis->patient_id, PatientStatus::AVAILABLE->value);
        });

        return true;
    }

    /**
     * رفض تشخيص طالب
     */
    public function rejectCase(int $id, array $data, int $instructorId, int $instructorProfileId)
    {
        $diagnosis = $this->diagnosisRepo->FindOrFail($id);

        $this->validatePendingStatus($diagnosis);
        $this->authorizeInstructorForStudent($diagnosis, $instructorProfileId);

        return DB::transaction(function () use ($diagnosis, $data, $instructorId) {
            $this->diagnosisRepo->update($diagnosis, [
                'status' => DiagnosisStatus::REJECTED->value,
                'instructor_id' => $instructorId,
                'rejection_reason' => $data['rejection_reason'],
            ]);

            $this->patientRepo->updateAvailability($diagnosis->patient_id, PatientStatus::WAITING_DIAGNOSIS->value);

            return true;
        });
    }

    private function validatePendingStatus($diagnosis)
    {
        if ($diagnosis->status->value !== DiagnosisStatus::WAITING_APPROVAL->value) {
            throw new Exception('This request has already been processed.');
        }
    }

    private function authorizeInstructorForStudent($diagnosis, int $instructorProfileId)
    {
        $isAuthorized = Group::whereHas('students', function ($studentQuery) use ($diagnosis) {
            $studentQuery->where('user_id', $diagnosis->suggested_by_student_id);
        })
            ->whereHas('instructors', function ($instructorQuery) use ($instructorProfileId) {
                $instructorQuery->where('instructor_profiles.id', $instructorProfileId);
            })
            ->exists();

        if (! $isAuthorized) {
            throw new Exception('You are not authorized to process this diagnosis because the student is outside your assigned groups.', 403);
        }
    }
}
