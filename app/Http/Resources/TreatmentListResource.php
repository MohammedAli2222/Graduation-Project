<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TreatmentListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $firstAppointment = $this->diagnosis?->appointments?->first();

        $student = $firstAppointment?->student;
        $patient = $firstAppointment?->patient;

        return [
            'id' => $this->id,
            'status' => $this->status,
            'start_date' => $this->start_date ? $this->start_date->toDateTimeString() : null,
            // student_name أُبقي عليه كما هو لعدم كسر الشاشات الحالية؛ student
            // كائن جديد يحمل نفس الاسم بالإضافة لفئة الطالب (group) لاستخدامه بفلتر الفئات
            'student_name' => $student ? ($student->first_name.' '.$student->last_name) : 'N/A',
            'student' => $student ? [
                'id' => $student->id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'group' => [
                    'id' => $student->studentProfile?->group?->id,
                    'group_name' => $student->studentProfile?->group?->group_name,
                ],
            ] : null,
            'patient_name' => $patient->full_name ?? 'N/A',
            'case_type' => $this->diagnosis->CaseType->name ?? 'N/A',
        ];
    }
}
