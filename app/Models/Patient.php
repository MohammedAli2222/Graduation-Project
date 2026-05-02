<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\PatientStatus;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Support\Str;

class Patient extends Model implements HasMedia
{

    use InteractsWithMedia;

    protected $fillable = [
        'patient_code',
        'full_name',
        'phone',
        'med_history',
        'preliminary_diagnosis',
        'availability_status',
        'added_by'
    ];

    protected function casts(): array
    {
        return [
            'availability_status' => PatientStatus::class,
        ];
    }

    protected static function booted()
    {
        static::creating(function ($patient) {

            $patient->patient_code = date('Y') . '-' . Str::upper(Str::random(4));

            $patient->availability_status = PatientStatus::WAITING_DIAGNOSIS;

            if (auth()->check()) {
                $patient->added_by = auth()->id();
            }else {
            $patient->added_by = 1;
        }
        });
    }
}
