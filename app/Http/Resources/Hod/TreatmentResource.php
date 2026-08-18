<?php

declare(strict_types=1);

namespace App\Http\Resources\Hod;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TreatmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,

            'status'           => $this->status->value ?? $this->status,
            'start_date'       => $this->start_date?->format('Y-m-d H:i:s'),
            'end_date'         => $this->end_date?->format('Y-m-d H:i:s'),
            'instructor_notes' => $this->instructor_notes,
            'rejection_reason' => $this->rejection_reason,

            'before_images' => $this->getMedia('before_treatment_images')->map(function ($media) {
                return [
                    'id' => $media->id,
                    'file_name' => $media->file_name,
                    'url' => $media->getUrl(),
                ];
            }),

            'after_images' => $this->getMedia('after_treatment_images')->map(function ($media) {
                return [
                    'id' => $media->id,
                    'file_name' => $media->file_name,
                    'url' => $media->getUrl(),
                ];
            }),

            'diagnosis' => $this->whenLoaded('diagnosis', function () {
                // الطالب المُنفِّذ الفعلي للعلاج يُستدل من الموعد المرتبط بالعلاج
                // (appointments.student)، وليس من diagnosis.suggested_by_student_id:
                // ذاك العمود يمثّل فقط من اقترح التشخيص أصلاً (غالباً null لأن أغلب
                // التشخيصات يضعها المعيد مباشرة)، وحتى لو كان موجوداً فأي طالب مؤهَّل
                // آخر قد يكون من حجز الموعد ونفّذ العلاج فعلياً.
                $treatingStudent = $this->appointments->first()?->student;

                return [
                    'id' => $this->diagnosis_id,
                    'case_type' => [
                        'id'   => $this->diagnosis->caseType?->id,
                        'name' => $this->diagnosis->caseType?->name,
                    ],
                    'department' => [
                        'id'   => $this->diagnosis->department?->id,
                        'name' => $this->diagnosis->department?->name,
                    ],
                    // Patient::$fillable لا يحتوي first_name/last_name، بل full_name فقط
                    'patient' => [
                        'id'        => $this->diagnosis->patient?->id,
                        'full_name' => $this->diagnosis->patient?->full_name,
                        'phone'     => $this->diagnosis->patient?->phone,
                    ],
                    'student' => [
                        'id'         => $treatingStudent?->id,
                        'first_name' => $treatingStudent?->first_name,
                        'last_name'  => $treatingStudent?->last_name,
                        'group' => [
                            'id'         => $treatingStudent?->studentProfile?->group?->id,
                            'group_name' => $treatingStudent?->studentProfile?->group?->group_name,
                        ],
                    ],
                ];
            }),

            'instructor' => $this->whenLoaded('instructor', function () {
                return [
                    'id'         => $this->instructor->id,
                    'first_name' => $this->instructor->first_name,
                    'last_name'  => $this->instructor->last_name,
                ];
            }),
        ];
    }
}
