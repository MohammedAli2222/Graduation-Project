<?php

namespace App\Models;

use App\Enums\PatientStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Patient extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'patient_code',
        'full_name',
        'gender',
        'phone',
        'preliminary_diagnosis',
        'availability_status',
        'added_by',
    ];

    public function medicalHistory()
    {
        return $this->hasOne(PatientMedicalHistory::class);
    }

    public function diagnoses()
    {
        return $this->hasMany(PatientDiagnose::class);
    }

    protected function casts(): array
    {
        return [
            'availability_status' => PatientStatus::class,
        ];
    }

    protected static function booted()
    {
        static::creating(function ($patient) {

            $patient->patient_code = date('Y').'-'.Str::upper(Str::random(4));

            $patient->availability_status = PatientStatus::WAITING_DIAGNOSIS;

            if (auth()->check()) {
                $patient->added_by = auth()->id();
            }
        });
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(100)
            ->queued();
    }
}
