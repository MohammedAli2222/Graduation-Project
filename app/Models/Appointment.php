<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'patient_id',
        'student_id',
        'diagnosis_id',
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

    /**
     * الموعد قد ينتج عنه عدة جلسات معالجة (عادة جلسة واحدة لكل موعد)
     */
    public function treatments()
    {
        return $this->hasMany(Treatment::class);
    }
}
