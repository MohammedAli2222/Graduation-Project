<?php

namespace App\Services;

use App\Enums\DiagnosisStatus;
use App\Enums\PatientStatus;
use App\Events\DiagnosisReviewedEvent;
use App\Events\NewDiagnosesAvailableEvent;
use App\Models\CaseType;
use App\Models\Group;
use App\Models\InstructorProfile;
use App\Models\Patient;
use App\Models\PatientDiagnose;
use App\Repositories\DiagnosisRepository;
use App\Repositories\PatientRepository;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Exception;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DiagnosisService
{
    public function __construct(
        protected DiagnosisRepository $diagnosisRepo,
        protected PatientRepository $patientRepo
    ) {}


    public function getInstructorStats(int $userId)
    {
        $instructorProfile = InstructorProfile::where('user_id', $userId)->firstOrFail();

        $groupIds = $instructorProfile->groups()->pluck('groups.id');

        $pendingRequests = PatientDiagnose::where('status', DiagnosisStatus::WAITING_APPROVAL->value)
            ->whereHas('student', function ($query) use ($groupIds) {
                $query->whereHas('studentProfile', function ($subQuery) use ($groupIds) {
                    $subQuery->whereIn('group_id', $groupIds);
                });
            })->count();

       $waitingPatients = Patient::where('availability_status', PatientStatus::WAITING_DIAGNOSIS->value)
        ->whereHas('adder', function ($query) {
            $query->role('receptionist');
        })
        ->count();

        return [
            'pending_approvals_count' => $pendingRequests,
            'new_patients_from_reception_count' => $waitingPatients,
            'supervised_groups_total' => $groupIds->count(),
        ];
    }
    public function storeMultiple(array $data, int $instructorId)
    {
        $lock = Cache::lock('lock:diagnose_patient:' . $data['patient_id'], 10);

        try {

            $lock->block(3);

            $createdDiagnoses = DB::transaction(function () use ($data, $instructorId) {
                $patient = $this->patientRepo->FindOrFail($data['patient_id']);

                if ($patient->availability_status !== PatientStatus::WAITING_DIAGNOSIS->value) {
                    throw new Exception('This patient is no longer waiting for diagnosis.', 409);
                }

                $hasPendingStudentDiagnosis = PatientDiagnose::where('patient_id', $data['patient_id'])
                    ->where('status', DiagnosisStatus::WAITING_APPROVAL->value)
                    ->exists();

                if ($hasPendingStudentDiagnosis) {
                    throw new Exception('This patient has a pending diagnosis from a student. Please approve or reject it from the pending requests list.', 422);
                }

                if (! auth()->user()->instructorProfile?->id) {
                    throw new Exception('Instructor profile not found.', 404);
                }

                $createdDiagnoses = [];

                foreach ($data['diagnoses'] as $item) {
                    $caseType = CaseType::with('course')->findOrFail($item['case_type_id']);

                    $diagnosis = $this->diagnosisRepo->create([
                        'patient_id' => $data['patient_id'],
                        'instructor_id' => $instructorId,
                        'case_type_id' => $item['case_type_id'],
                        'department_id' => $caseType->course->department_id,
                        'final_diagnosis' => $item['final_diagnosis'],
                        'status' => DiagnosisStatus::AVAILABLE->value,
                    ]);

                    if (!empty($item['media_ids'])) {
                        foreach ($item['media_ids'] as $mediaId) {
                            $media = Media::where('id', $mediaId)
                                ->where('model_id', $data['patient_id'])
                                ->where('model_type', Patient::class)
                                ->first();

                            if ($media) {
                                $media->copy($diagnosis, $media->collection_name);
                            }
                        }
                    }

                    $createdDiagnoses[] = $diagnosis;
                }

                $this->patientRepo->updateAvailability($data['patient_id'], PatientStatus::AVAILABLE->value);
                return $createdDiagnoses;
            });

            // إطلاق الحدث بعد نجاح المعاملة لإشعار الطلاب المسجلين بالحالات الجديدة دون تأخير استجابة الـ API الرئيسية
            NewDiagnosesAvailableEvent::dispatch($createdDiagnoses);

            return $createdDiagnoses;
        } catch (LockTimeoutException $e) {
            throw new Exception('This patient is currently being diagnosed by another instructor.', 409);
        } finally {
            optional($lock)->release();
        }
    }

    public function approveCase(int $id, array $data, int $instructorId, int $instructorProfileId)
    {
        $lock = Cache::lock('lock:review_diagnosis:' . $id, 10);

        try {

            $lock->block(3);

            $diagnosis = DB::transaction(function () use ($id, $data, $instructorId, $instructorProfileId) {
                $diagnosis = $this->diagnosisRepo->FindOrFail($id);

                $this->validatePendingStatus($diagnosis);
                $this->authorizeInstructorForStudent($diagnosis, $instructorProfileId);

                $this->diagnosisRepo->update($diagnosis, [
                    'status' => DiagnosisStatus::AVAILABLE->value,
                    'instructor_id' => $instructorId,
                    'final_diagnosis' => $data['final_diagnosis'],
                ]);

                $this->patientRepo->updateAvailability($diagnosis->patient_id, PatientStatus::AVAILABLE->value);

                return $diagnosis;
            });

            // إطلاق الحدث بعد نجاح المعاملة لإشعار الطالب بالموافقة دون تأخير استجابة الـ API الرئيسية
            DiagnosisReviewedEvent::dispatch($diagnosis, DiagnosisStatus::AVAILABLE->value);

            return true;
        } catch (LockTimeoutException $e) {
            throw new Exception('This diagnosis request is currently being reviewed by another instructor.', 409);
        } finally {
            optional($lock)->release();
        }
    }

    public function rejectCase(int $id, array $data, int $instructorId, int $instructorProfileId)
    {
        $lock = Cache::lock('lock:review_diagnosis:' . $id, 10);

        try {

            $lock->block(3);

            $diagnosis = DB::transaction(function () use ($id, $data, $instructorId, $instructorProfileId) {
                $diagnosis = $this->diagnosisRepo->FindOrFail($id);

                $this->validatePendingStatus($diagnosis);
                $this->authorizeInstructorForStudent($diagnosis, $instructorProfileId);

                $this->diagnosisRepo->update($diagnosis, [
                    'status' => DiagnosisStatus::REJECTED->value,
                    'instructor_id' => $instructorId,
                    'rejection_reason' => $data['rejection_reason'],
                ]);

                $this->patientRepo->updateAvailability($diagnosis->patient_id, PatientStatus::WAITING_DIAGNOSIS->value);

                return $diagnosis;
            });

            // إطلاق الحدث بعد نجاح المعاملة لإشعار الطالب بالرفض دون تأخير استجابة الـ API الرئيسية
            DiagnosisReviewedEvent::dispatch($diagnosis, DiagnosisStatus::REJECTED->value);

            return true;
        } catch (LockTimeoutException $e) {
            throw new Exception('This diagnosis request is currently being reviewed by another instructor.', 409);
        } finally {
            optional($lock)->release();
        }
    }

    // إحالة المريض إلى قسم آخر عبر إضافة نوع حالة جديد يخص ذلك القسم، ليظهر فوراً لطلاب القسم المُحال إليه
    public function referPatientToDepartment(int $patientId, array $data, int $instructorId)
    {
        $diagnosis = DB::transaction(function () use ($patientId, $data, $instructorId) {
            $patient = $this->patientRepo->FindOrFail($patientId);

            $caseType = CaseType::with('course')->findOrFail($data['case_type_id']);

            $diagnosis = $this->diagnosisRepo->create([
                'patient_id' => $patient->id,
                'instructor_id' => $instructorId,
                'case_type_id' => $caseType->id,
                'department_id' => $caseType->course->department_id,
                'final_diagnosis' => $data['referral_notes'],
                'status' => DiagnosisStatus::AVAILABLE->value,
            ]);

            $this->patientRepo->updateAvailability($patient->id, PatientStatus::AVAILABLE->value);

            return $diagnosis;
        });

        // إطلاق الحدث بعد نجاح المعاملة لإشعار طلاب القسم المُحال إليه فوراً بالحالة الجديدة
        NewDiagnosesAvailableEvent::dispatch([$diagnosis]);

        return $diagnosis;
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
