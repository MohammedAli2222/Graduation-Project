<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;
    protected $fillable = [
        'patient_id',
        'student_id',
        'diagnosis_id',
        'treatment_id',
        'slot_number',
        'appointment_date',
        'status',
    ];

    protected $casts = [
        'status' => AppointmentStatus::class,
        'appointment_date' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function diagnosis()
    {
        return $this->belongsTo(PatientDiagnose::class, 'diagnosis_id');
    }

    public function treatment()
    {
        return $this->belongsTo(Treatment::class, 'treatment_id');
    }

    public function getSlotTimeRange(): string
    {
        // تعريف الأوقات لكل سلوت
        $slotDefinitions = [
            1 => '08:00 AM - 10:00 AM',
            2 => '10:30 AM - 12:30 PM',
            3 => '01:00 PM - 03:00 PM',
            4 => '03:30 PM - 05:30 PM',
        ];

        return $slotDefinitions[$this->slot_number] ?? '';
    }

}

