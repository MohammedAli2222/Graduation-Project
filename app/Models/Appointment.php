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
        'treatment_id',
        'start_slot',
        'end_slot',
        'slots_count',
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

        $ranges = [];

        // نقوم بالدوران من بداية السلوت إلى نهايته
        for ($i = $this->start_slot; $i <= $this->end_slot; $i++) {
            if (isset($slotDefinitions[$i])) {
                $ranges[] = $slotDefinitions[$i];
            }
        }

        // إذا كان الموعد سلوت واحد، نرجعه مباشرة
        if (count($ranges) === 1) {
            return $ranges[0];
        }

        // إذا كان أكثر من سلوت، ندمجهم بطريقة جميلة
        return implode(' & ', $ranges);
    }

    public function getIsFollowUpAttribute(): bool
    {
        // إذا لم يكن هناك علاج مرتبط، فهو ليس follow-up
        if (! $this->treatment_id) {
            return false;
        }

        // نفحص ما إذا كان هناك موعد بنفس الـ treatment_id أنشئ قبله
        // نستخدم created_at أو id للمقارنة
        return Appointment::where('treatment_id', $this->treatment_id)
            ->where('id', '<', $this->id)
            ->exists();
    }
}
