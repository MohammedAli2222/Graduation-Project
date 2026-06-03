<?php

namespace App\Repositories;

use App\Enums\DiagnosisStatus;
use App\Enums\PatientStatus;
use App\Models\Patient;

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
                }
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

    public function getReceptionistStatsByStatus(int $receptionistId,  $status = null)
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
}
