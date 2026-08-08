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
        'birth_date',
        'address',
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

    public function adder()
    {
        return $this->belongsTo(User::class, 'added_by');
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

            // زيادة طول الجزء العشوائي من 4 إلى 8 محارف لتقليل احتمال تصادم
            // القيم (patient_code فريد UNIQUE في قاعدة البيانات) عند توليد
            // أعداد كبيرة من المرضى دفعة واحدة (كما في Seeders الاختبار)
            $patient->patient_code = date('Y') . '-' . Str::upper(Str::random(8));

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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('id_cards');
        $this->addMediaCollection('clinical_images');
        $this->addMediaCollection('x_ray_images');
    }
}
