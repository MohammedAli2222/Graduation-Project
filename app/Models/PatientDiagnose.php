<?php

namespace App\Models;

use App\Enums\DiagnosisStatus;
use Illuminate\Database\Eloquent\Model;

class PatientDiagnose extends Model
{
    protected $fillable = [
        'patient_id',
        'instructor_id',
        'case_type_id',
        'department_id',
        'suggested_by_student_id',
        'final_diagnosis',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'status' => DiagnosisStatus::class,
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }


    public function student()
    {
        return $this->belongsTo(User::class, 'suggested_by_student_id');
    }

    public function caseType()
    {
        return $this->belongsTo(CaseType::class);
    }


    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function treatments()
{
    return $this->hasManyThrough(
        Treatment::class,     // الموديل النهائي
        Appointment::class,   // الموديل الوسيط
        'diagnosis_id',       // المفتاح الأجنبي في الموعد
        'appointment_id',     // المفتاح الأجنبي في المعالجة
        'id',                 // المفتاح الأساسي في التشخيص
        'id'                  // المفتاح الأساسي في الموعد
    );
}
}
