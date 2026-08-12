<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientDiagnosisDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'diagnosis_id' => $this->id,
            'patient_info' => [
                'id' => $this->patient_id,
                'name' => $this->patient->full_name ?? 'N/A',
                'gender' => $this->patient->gender ?? 'N/A',
                'phone' => $this->patient->phone ?? 'N/A',
                'birth_date' => $this->patient->birth_date ?? 'N/A',
                'address' => $this->patient->address ?? 'N/A',
            ],
            'medical_history' => $this->patient->medicalHistory ? [
                'has_general_diseases' => (bool) $this->patient->medicalHistory->has_general_diseases,
                'general_diseases_details' => $this->patient->medicalHistory->general_diseases_details ?? 'لا يوجد أمراض عامة',

                'is_special_needs' => (bool) $this->patient->medicalHistory->is_special_needs,
                'special_needs_details' => $this->patient->medicalHistory->special_needs_details ?? 'لا يوجد احتياجات خاصة',

                'takes_medications' => (bool) $this->patient->medicalHistory->takes_medications,
                'medications_details' => $this->patient->medicalHistory->medications_details ?? 'لا يتناول أدوية',

                'has_allergies' => (bool) $this->patient->medicalHistory->has_allergies,
                'allergies_details' => $this->patient->medicalHistory->allergies_details ?? 'لا يوجد حساسية',
            ] : null,

            'diagnosis_details' => [
                'case_type' => $this->caseType->name ?? 'N/A',
                'department' => $this->department->name ?? 'N/A',
                'final_diagnosis' => $this->final_diagnosis,
                'case_status' => $this->status,
                'estimated_cost' => $this->estimated_cost

            ],
            'attachments' => [
                'patient_documents' => $this->patient->getMedia('id_cards')->map(fn($m) => [
                    'id' => $m->id,
                    'url' => $m->getUrl(),
                ])->values(),

                'clinical_images' => $this->collectMedia('clinical_images'),

                'x_ray_images' => $this->collectMedia('x_ray_images'),
            ],
            'instructor_name' => trim(($this->instructor->first_name ?? 'N/A') . ' ' . ($this->instructor->last_name ?? '')),
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i') : null,
        ];
    }

    /**
     * يجمع صور المجموعة من مصدرَيها معاً بدل الاكتفاء بمصدر واحد:
     *
     * - صور المريض: يرفعها موظف الاستقبال وتُربط بسجل المريض نفسه.
     * - صور التشخيص: يرفعها الطالب مع اقتراحه، أو ينسخها المعيد عبر media_ids
     *   عند التشخيص المباشر.
     *
     * كان التابع يقرأ من التشخيص فقط، فتختفي صور الاستقبال كلياً عن الطالب
     * إن لم ينسخها المعيد يدوياً.
     *
     * إزالة التكرار على file_name: نسخة الوسائط (Media::copy) تحتفظ باسم الملف
     * نفسه وتغيّر الـ id فقط، فلولا ذلك لظهرت الصورة المنسوخة مرتين.
     */
    private function collectMedia(string $collection)
    {
        return $this->patient->getMedia($collection)
            ->concat($this->getMedia($collection))
            ->unique('file_name')
            ->map(fn($m) => [
                'id' => $m->id,
                'url' => $m->getUrl(),
            ])
            ->values();
    }
}
