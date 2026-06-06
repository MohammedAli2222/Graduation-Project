<?php

namespace App\Repositories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Department;
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

    public function hasConflict(int $studentId, string $dateString, int $slotNumber, int $departmentId): array
    {
        $dateOnly = Carbon::parse($dateString)->format('Y-m-d');

        $existingAppointments = Appointment::where('student_id', $studentId)
            ->whereIn('status', [AppointmentStatus::SCHEDULED->value, AppointmentStatus::ATTENDED->value])
            ->whereDate('appointment_date', $dateOnly)
            ->get();

        foreach ($existingAppointments as $appointment) {
            if ((int)$appointment->slot_number === (int)$slotNumber) {
                return [
                    'conflict' => true,
                    'message' => 'Time conflict! You already have another appointment booked in this exact time slot.'
                ];
            }
        }

        $department = Department::find($departmentId);
        $maxChairs = $department->total_chairs ;

        $reservedChairsCount = Appointment::whereIn('status', [AppointmentStatus::SCHEDULED->value, AppointmentStatus::ATTENDED->value])
            ->whereDate('appointment_date', $dateOnly)
            ->where('slot_number', $slotNumber)
            ->whereHas('diagnosis', function ($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            })
            ->count();

        if ($reservedChairsCount >= $maxChairs) {
            return [
                'conflict' => true,
                'message' => "This time slot is fully booked. All {$maxChairs} dental chairs in this department are occupied."
            ];
        }

        return ['conflict' => false, 'message' => ''];
    }

    
    public function getActiveAppointmentsCountForStudent(int $studentId, string $date): int
    {
        return Appointment::where('student_id', $studentId)
            ->whereIn('status', [
                AppointmentStatus::SCHEDULED->value,
                AppointmentStatus::ATTENDED->value
            ])
            ->whereDate('appointment_date', $date)
            ->count();
    }
}
