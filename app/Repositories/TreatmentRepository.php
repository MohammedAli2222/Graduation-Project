<?php

namespace App\Repositories;

use App\Enums\TreatmentStatus;
use App\Models\Treatment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TreatmentRepository
{
    public function create(array $data): Treatment
    {
        return Treatment::create($data);
    }

    public function find($id)
    {
        return Treatment::with('diagnosis')->find($id);
    }

    public function updateStatus(int $treatmentId, string $status): bool
    {
        return Treatment::where('id', $treatmentId)->update(['status' => $status]);
    }

    public function getHistoryByPatientId(int $patientId)
    {
        return Treatment::whereHas('diagnosis', function ($query) use ($patientId) {
            $query->where('patient_id', $patientId);
        })
            ->whereIn('status', [
                TreatmentStatus::COMPLETED->value,
                TreatmentStatus::IN_PROGRESS->value,
                TreatmentStatus::WAITING_INSTRUCTOR_APPROVAL->value,
            ])
            ->with([
                'diagnosis' => function ($query) {
                    $query->select('id', 'patient_id', 'case_type_id', 'department_id', 'final_diagnosis', 'status', 'created_at');
                },
                'appointments' => function ($query) {
                    $query->select('id', 'treatment_id', 'appointment_date', 'status', 'slot_number', 'created_at', 'updated_at')
                        ->orderBy('appointment_date', 'desc');
                },
            ])
            ->latest('id')
            ->get();
    }

    public function getPendingApprovalsListForInstructor(int $instructorProfileId, int $perPage = 10)
    {
        return Treatment::query()
            ->where('status', TreatmentStatus::WAITING_INSTRUCTOR_APPROVAL->value)
            ->whereHas('diagnosis.appointments.student.studentProfile', function ($query) use ($instructorProfileId) {
                $query->whereHas('group.instructors', function ($q) use ($instructorProfileId) {
                    $q->where('instructor_profiles.id', $instructorProfileId);
                });
            })
            ->with([
                'diagnosis.appointments.student:id,first_name,last_name',
                'diagnosis.appointments.patient:id,full_name',
                'diagnosis.caseType:id,name',
            ])
            ->latest()
            ->paginate($perPage);
    }

    public function getTreatmentDetailsForInstructor(int $treatmentId, int $instructorProfileId)
    {
        return Treatment::query()
            ->where('id', $treatmentId)
            ->whereHas('diagnosis.appointments.student.studentProfile', function ($query) use ($instructorProfileId) {
                $query->whereHas('group.instructors', function ($q) use ($instructorProfileId) {
                    $q->where('instructor_profiles.id', $instructorProfileId);
                });
            })
            ->with([
                'media',
                'diagnosis.appointments.student',
                'diagnosis.appointments.patient',
                'appointments',
                'instructor',
            ])
            ->first();
    }

    public function getStudentProgressStats(int $userId)
    {
        return Cache::remember("student_progress_stats_{$userId}", 300, function () use ($userId) {
            return $this->fetchStudentProgressStats($userId);
        });
    }

    public function clearStudentProgressCache(int $userId): void
    {
        Cache::forget("student_progress_stats_{$userId}");
    }

    private function fetchStudentProgressStats(int $userId)
    {
        $studentProfile = \DB::table('student_profiles')->where('user_id', $userId)->first();

        if (! $studentProfile) {
            return null;
        }

        $profileId = $studentProfile->id;

        $treatmentsCount = DB::table('treatments')
            ->join('patient_diagnoses', 'treatments.diagnosis_id', '=', 'patient_diagnoses.id')
            ->join('case_types', 'patient_diagnoses.case_type_id', '=', 'case_types.id')
            ->join('student_course_enrollments', function ($join) use ($profileId) {
                $join->on('case_types.course_id', '=', 'student_course_enrollments.course_id')
                    ->where('student_course_enrollments.student_id', $profileId)
                    ->where('student_course_enrollments.status', 'active');
            })
            ->join('appointments', 'patient_diagnoses.id', '=', 'appointments.diagnosis_id')
            ->where('appointments.student_id', $userId)
            ->selectRaw("
            COUNT(DISTINCT treatments.id) as total,
            COUNT(DISTINCT CASE WHEN treatments.status = 'completed' THEN treatments.id END) as completed,
            COUNT(DISTINCT CASE WHEN treatments.status = 'waiting_instructor_approval' THEN treatments.id END) as pending,
            COUNT(DISTINCT CASE WHEN treatments.status = 'rejected' THEN treatments.id END) as rejected
        ")
            ->first();

        $casesByType = DB::table('student_course_enrollments')
            ->join('case_types', 'student_course_enrollments.course_id', '=', 'case_types.course_id')
            ->join('courses', 'case_types.course_id', '=', 'courses.id')
            ->leftJoin('patient_diagnoses', 'case_types.id', '=', 'patient_diagnoses.case_type_id')
            ->leftJoin('treatments', 'patient_diagnoses.id', '=', 'treatments.diagnosis_id')
            ->leftJoin('appointments', function ($join) use ($userId) {
                $join->on('patient_diagnoses.id', '=', 'appointments.diagnosis_id')
                    ->where('appointments.student_id', $userId);
            })
            ->where('student_course_enrollments.student_id', $profileId)
            ->where('student_course_enrollments.status', 'active')
            ->groupBy('case_types.id', 'case_types.name', 'case_types.required_count', 'courses.name')
            ->selectRaw("
            case_types.id as type_id,
            case_types.name as case_type_name,
            courses.name as course_name,
            case_types.required_count as required_to_pass,
            COUNT(DISTINCT CASE WHEN treatments.status = 'completed' THEN treatments.id END) as completed_count,
            COUNT(DISTINCT CASE WHEN treatments.status = 'in_progress' THEN treatments.id END) as in_progress_count
        ")
            ->get();

        $appointmentsCount = DB::table('appointments')
            ->join('patient_diagnoses', 'appointments.diagnosis_id', '=', 'patient_diagnoses.id')
            ->join('case_types', 'patient_diagnoses.case_type_id', '=', 'case_types.id')
            ->join('student_course_enrollments', function ($join) use ($profileId) {
                $join->on('case_types.course_id', '=', 'student_course_enrollments.course_id')
                    ->where('student_course_enrollments.student_id', $profileId)
                    ->where('student_course_enrollments.status', 'active');
            })
            ->where('appointments.student_id', $userId)
            ->selectRaw("
            COUNT(appointments.id) as total,
            COUNT(CASE WHEN appointments.status = 'attended' THEN 1 END) as attended
        ")
            ->first();

        return [
            'treatments'   => $treatmentsCount,
            'types_detail' => $casesByType,
            'appointments' => $appointmentsCount,
        ];
    } // end fetchStudentProgressStats


    public function getStudentTreatmentsList(int $userId, string $statusType, int $perPage = 10)
    {
        $query = Treatment::query()
            ->with([
                'diagnosis.patient:id,full_name',
                'diagnosis.caseType.course:id,name',
            ])
            ->whereHas('diagnosis.appointments', function ($q) use ($userId) {
                $q->where('student_id', $userId);
            });

        if ($statusType === 'completed') {
            $query->where('status', 'completed');
        } else {
            $query->whereIn('status', ['in_progress', 'waiting_instructor_approval', 'rejected']);
        }

        return $query->orderBy('updated_at', 'desc')->paginate($perPage);
    }
}
