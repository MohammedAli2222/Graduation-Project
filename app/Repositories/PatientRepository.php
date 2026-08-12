<?php

namespace App\Repositories;

use App\Enums\DiagnosisStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\PatientStatus;
use App\Models\Course;
use App\Models\Patient;
use App\Models\PatientDiagnose;
use App\Models\StudentCourseEnrollment;

class PatientRepository
{
    public function FindOrFail(int $id)
    {
        return Patient::findOrFail($id);
    }

    public function create(array $data)
    {
        return Patient::create($data);
    }

    public function search(string $term)
    {
        return Patient::with('media')
            ->where('patient_code', $term)
            ->orWhere('phone', $term)
            ->first();
    }

    public function findWithMedia(int $id)
    {
        return Patient::with('media')->findOrFail($id);
    }

    public function getReceptionistWaitingList()
    {
        return Patient::with(['media', 'medicalHistory'])
            ->where('availability_status', PatientStatus::WAITING_DIAGNOSIS->value)
            ->whereDoesntHave('diagnoses')
            ->orderBy('created_at', 'asc')
            ->paginate(10);
    }

    public function getStudentPendingRequests(int $instructorProfileId)
    {
        return Patient::whereHas('diagnoses', function ($query) use ($instructorProfileId) {
            $query->where('status', DiagnosisStatus::WAITING_APPROVAL->value)
                ->whereHas('student.studentProfile.group.instructors', function ($instructorQuery) use ($instructorProfileId) {
                    $instructorQuery->where('instructor_profiles.id', $instructorProfileId);
                });
        })
            ->with([
                'media',
                'medicalHistory',
                'diagnoses' => function ($query) use ($instructorProfileId) {
                    $query->where('status', DiagnosisStatus::WAITING_APPROVAL->value)
                        ->whereHas('student.studentProfile.group.instructors', function ($instructorQuery) use ($instructorProfileId) {
                            $instructorQuery->where('instructor_profiles.id', $instructorProfileId);
                        })
                        ->with(['caseType', 'student.studentProfile.group']);
                },
            ])
            ->orderBy('created_at', 'asc')
            ->paginate(10);
    }

    public function update(int $id, array $data)
    {
        $patient = Patient::findOrFail($id);
        $patient->update($data);

        return $patient;
    }

    public function getReceptionistStatsByStatus(int $receptionistId, $status = null)
    {
        $query = Patient::where('added_by', $receptionistId)
            ->whereDate('created_at', now()->today());

        if ($status) {
            $query->where('availability_status', $status);
        }

        return $query->count();
    }

    public function updateAvailability(int $patientId, string $status)
    {
        return Patient::where('id', $patientId)->update(['availability_status' => $status]);
    }

    public function getByCaseTypeAndStatus(int $caseTypeId, string $statusValue)
    {
        return PatientDiagnose::select(
            'id',
            'patient_id',
            'case_type_id',
            'department_id',
            'instructor_id',
            'status',
            'estimated_cost',
            'final_diagnosis',
            'created_at'
        )
            ->with([
                'patient' => function ($query) {
                    $query->select('id', 'full_name', 'phone', 'gender', 'birth_date', 'address');
                },
                'caseType' => function ($query) {
                    $query->select('id', 'name');
                },
                'department' => function ($query) {
                    $query->select('id', 'name');
                },
                'instructor' => function ($query) {
                    $query->select('id', 'first_name', 'last_name');
                },
            ])
            ->where('case_type_id', $caseTypeId)
            ->where('status', $statusValue)
            ->latest()
            ->paginate(15);
    }


    public function checkIfCaseBelongsToActiveCourse(int $caseTypeId, int $studentId): bool
    {
        return StudentCourseEnrollment::where('student_id', $studentId)
            ->where('status', EnrollmentStatus::ACTIVE)
            ->whereHas('course.caseTypes', function ($query) use ($caseTypeId) {
                $query->where('id', $caseTypeId);
            })
            ->exists();
    }

    public function getDiagnosisDetailsWithPatientMedia(int $id)
    {
        return PatientDiagnose::with([
            'patient' => fn($q) => $q->select('id', 'full_name', 'phone', 'gender', 'birth_date', 'address'),
            'patient.media',
            'patient.medicalHistory',
            // وسائط التشخيص نفسه: يقرأها PatientDiagnosisDetailsResource إلى جانب
            // وسائط المريض، وبدون تحميلها مسبقاً ينطلق استعلام إضافي عند بناء الرد
            'media',
            'caseType' => fn($q) => $q->select('id', 'name'),
            'department' => fn($q) => $q->select('id', 'name'),
            'instructor' => fn($q) => $q->select('id', 'first_name', 'last_name'),
        ])
            ->where(function ($query) use ($id) {
                $query->where('id', $id)
                    ->where(function ($q) {
                        $q->where('status', DiagnosisStatus::AVAILABLE->value)
                            ->orWhereHas('appointments', function ($appointmentQuery) {
                                $appointmentQuery->where('student_id', auth()->id());
                            });
                    });
            })
            ->findOrFail($id);
    }
    // public function getDiagnosisDetailsWithPatientMedia(int $id)
    // {
    //     return PatientDiagnose::select(
    //         'id',
    //         'patient_id',
    //         'case_type_id',
    //         'department_id',
    //         'instructor_id',
    //         'status',
    //         'estimated_cost',
    //         'final_diagnosis',
    //         'created_at'
    //     )
    //         ->with([
    //             'patient' => function ($query) {
    //                 $query->select('id', 'full_name', 'phone', 'gender', 'birth_date', 'address');
    //             },
    //             'patient.media',
    //             'patient.medicalHistory',

    //             'caseType' => function ($query) {
    //                 $query->select('id', 'name');
    //             },
    //             'department' => function ($query) {
    //                 $query->select('id', 'name');
    //             },
    //             'instructor' => function ($query) {
    //                 $query->select('id', 'first_name', 'last_name');
    //             },
    //         ])
    //         ->where('status', DiagnosisStatus::AVAILABLE->value)
    //         ->findOrFail($id);
    // }

    public function getAvailableDiagnosesForPatient(int $patientId, int $studentId)
    {
        $activeCourseIds = StudentCourseEnrollment::where('student_id', $studentId)
            ->where('status', EnrollmentStatus::ACTIVE)
            ->pluck('course_id');

        return PatientDiagnose::where('patient_id', $patientId)
            ->whereHas('caseType', function ($query) use ($activeCourseIds) {
                $query->whereIn('course_id', $activeCourseIds);
            })
            ->with(['caseType', 'department', 'instructor'])
            ->latest()
            ->get();
    }
}
