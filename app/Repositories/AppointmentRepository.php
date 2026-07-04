<?php

namespace App\Repositories;

use App\Enums\AppointmentStatus;
use App\Enums\DiagnosisStatus;
use App\Enums\PatientStatus;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Patient;
use App\Models\PatientDiagnose;
use Carbon\Carbon;

class AppointmentRepository
{
    public function create(array $data)
    {
        return Appointment::create($data);
    }

    public function findById(int $appointmentId)
    {
        return Appointment::findorFail($appointmentId);
    }

    public function updateStatus(int $appointmentId, string $status): bool
    {
        return Appointment::where('id', $appointmentId)->update(['status' => $status]);
    }

    public function countAppointmentsForTreatment(int $treatmentId): int
    {
        return Appointment::where('treatment_id', $treatmentId)->count();
    }

    public function cancelCurrentAttendedAppointment(int $treatmentId): bool
    {
        return Appointment::where('treatment_id', $treatmentId)
            ->where('status', AppointmentStatus::ATTENDED->value)
            ->update(['status' => AppointmentStatus::CANCELLED->value]);
    }

    public function countActiveAppointmentsForDiagnosis(int $diagnosisId): int
    {
        return Appointment::where('diagnosis_id', $diagnosisId)
            ->where('status', '!=', AppointmentStatus::CANCELLED->value)
            ->count();
    }



    public function getActiveAppointmentsCountForStudent(int $studentId, string $date): int
    {
        return Appointment::where('student_id', $studentId)
            ->whereIn('status', [
                AppointmentStatus::SCHEDULED->value,
                AppointmentStatus::ATTENDED->value,
            ])
            ->whereDate('appointment_date', $date)
            ->count();
    }

    

    public function hasAppointmentWithPatient(int $studentId, int $patientId): bool
    {
        return Appointment::where('student_id', $studentId)
            ->whereHas('diagnosis', function ($query) use ($patientId) {
                $query->where('patient_id', $patientId);
            })
            ->exists();
    }

    public function getStudentAppointments(int $studentId)
    {
        $today = Carbon::now('Asia/Damascus')->format('Y-m-d');

        return Appointment::where('student_id', $studentId)
            ->where('status', AppointmentStatus::SCHEDULED->value)
            ->where('appointment_date', '>=', $today)
            ->with([
                'diagnosis.caseType:id,name',
                'diagnosis.patient:id,full_name,phone',
                'diagnosis.department:id,name'
            ])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('slot_number', 'asc')
            ->paginate(10);
    }

    public function getStudentAppointmentHistory(int $studentId)
    {
        // نحدد التاريخ الحالي بتوقيت دمشق
        $today = Carbon::now('Asia/Damascus')->format('Y-m-d');

        return Appointment::where('student_id', $studentId)
            ->where(function ($query) use ($studentId, $today) {
                // الحالة 1: المواعيد التي انتهت فعلياً (حضور أو إلغاء)
                $query->whereNotIn('status', [AppointmentStatus::SCHEDULED->value])
                    ->orWhere(function ($q) use ($studentId, $today) {
                        $q->where('student_id', $studentId)
                            ->where('status', AppointmentStatus::SCHEDULED->value)
                            ->where('appointment_date', '<', $today);
                    });
            })
            ->with([
                'diagnosis.caseType:id,name',
                'diagnosis.patient:id,full_name,phone',
                'diagnosis.department:id,name'
            ])
            ->orderBy('appointment_date', 'desc')
            ->paginate(10);
    }

    public function isPatientBusyAtSlot(int $patientId, string $date, int $slotNumber): bool
    {
        return Appointment::where('patient_id', $patientId)
            ->whereDate('appointment_date', $date)
            ->where('slot_number', $slotNumber)
            ->whereIn('status', [AppointmentStatus::SCHEDULED->value, AppointmentStatus::ATTENDED->value])
            ->exists();
    }

    public function getStudentDailyUsage(int $studentId, string $date): int
    {
        return Appointment::where('student_id', $studentId)
            ->whereDate('appointment_date', $date)
            ->whereIn('status', [AppointmentStatus::SCHEDULED->value, AppointmentStatus::ATTENDED->value])
            ->sum('slots_count'); // مجموع السلوتس المحجوزة فعلياً
    }

    public function hasOverlap(int $ownerId, string $date, int $newStart, int $newEnd, string $column): bool
    {
        return Appointment::where($column, $ownerId)
            ->whereDate('appointment_date', $date)
            ->whereIn('status', [AppointmentStatus::SCHEDULED->value, AppointmentStatus::ATTENDED->value])
            ->where(function ($query) use ($newStart, $newEnd) {
                $query->where('start_slot', '<=', $newEnd)
                    ->where('end_slot', '>=', $newStart);
            })
            ->exists();
    }

    public function isDepartmentFull(int $departmentId, string $date, int $start, int $end): bool
    {
        $department = Department::find($departmentId);
        $maxChairs = $department->total_chairs;

        // بما أن الموعد قد يغطي عدة سلوتس، يجب أن نتحقق من كل سلوت على حدة
        for ($i = $start; $i <= $end; $i++) {
            $reservedChairsCount = Appointment::whereIn('status', [AppointmentStatus::SCHEDULED->value, AppointmentStatus::ATTENDED->value])
                ->whereDate('appointment_date', $date)
                ->where('start_slot', '<=', $i) // الموعد بدأ قبل أو في هذا السلوت
                ->where('end_slot', '>=', $i)   // الموعد انتهى بعد أو في هذا السلوت
                ->whereHas('diagnosis', function ($query) use ($departmentId) {
                    $query->where('department_id', $departmentId);
                })
                ->count();

            if ($reservedChairsCount >= $maxChairs) {
                return true; // القسم ممتلئ في هذا السلوت المحدد
            }
        }

        return false;
    }

    public function updatePatientAvailabilityStatus(int $patientId)
    {
        $hasAvailableDiagnoses = PatientDiagnose::where('patient_id', $patientId)
            ->where('status', DiagnosisStatus::AVAILABLE->value)
            ->exists();

        $newStatus = $hasAvailableDiagnoses ? PatientStatus::AVAILABLE : PatientStatus::FULLY_RESERVED;

        Patient::where('id', $patientId)->update(['availability_status' => $newStatus->value]);
    }
}
