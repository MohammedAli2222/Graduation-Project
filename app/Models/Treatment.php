<?php

namespace App\Models;

use App\Enums\TreatmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Treatment extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasFactory;

    protected $fillable = [
        'diagnosis_id',
        'instructor_id',
        'status',
        'start_date',
        'end_date',
        'instructor_notes',
    ];

    protected $casts = [
        'status' => TreatmentStatus::class,
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function diagnosis()
    {
        return $this->belongsTo(PatientDiagnose::class, 'diagnosis_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'treatment_id');
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
