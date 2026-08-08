<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * طلب بدء جلسة علاج من الطالب.
 *
 * نفس النقطة تخدم حالتين:
 * 1) بدء علاج جديد → صور ما قبل المعالجة إلزامية (بداية دورة حياة الحالة).
 * 2) حضور موعد متابعة لعلاج قائم → لا صور مطلوبة، فالعلاج بدأ أصلاً.
 *
 * لذلك يُحسب شرط الإلزام ديناميكياً من الموعد نفسه بدل جعله ثابتاً، وكانت
 * القاعدة السابقة nullable فتسمح ببدء علاج جديد بلا صور إطلاقاً.
 */
class StartTreatmentRequest extends FormRequest
{
    /**
     * الموعد المطلوب بدء العلاج عليه — يُحمَّل مرة واحدة ويُعاد استخدامه،
     * لأن قاعدة الإلزام قد تُستدعى أكثر من مرة أثناء التحقق.
     */
    private ?Appointment $resolvedAppointment = null;

    private bool $appointmentResolved = false;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'appointment_id' => ['required', 'integer', 'exists:appointments,id'],

            // إلزامية فقط عند إنشاء علاج جديد (موعد بلا treatment_id).
            'before_images' => [
                Rule::requiredIf(fn (): bool => $this->startsNewTreatment()),
                'array',
                'min:1',
                'max:5',
            ],
            'before_images.*' => ['image', 'mimes:jpeg,png,jpg', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'appointment_id.required' => 'The appointment ID is required to start a treatment session.',
            'appointment_id.exists' => 'The selected appointment does not exist.',

            'before_images.required' => 'Before-treatment images are mandatory when starting a new treatment.',
            'before_images.min' => 'At least one before-treatment image must be uploaded.',
            'before_images.max' => 'You cannot upload more than 5 before-treatment images.',
            'before_images.*.image' => 'Each before-treatment file must be a valid image.',
            'before_images.*.mimes' => 'Before-treatment images must be of type: jpeg, png, jpg.',
            'before_images.*.max' => 'Each before-treatment image must not exceed 5 MB.',
        ];
    }

    /**
     * هل سيُنشئ هذا الطلب علاجاً جديداً؟ (الموعد غير مرتبط بعلاج قائم)
     *
     * إذا كان الموعد غير موجود نُرجع false ونترك قاعدة exists تتكفّل بالخطأ،
     * حتى لا تظهر رسالتا خطأ متناقضتان للطلب نفسه.
     */
    private function startsNewTreatment(): bool
    {
        $appointment = $this->resolveAppointment();

        return $appointment !== null && $appointment->treatment_id === null;
    }

    private function resolveAppointment(): ?Appointment
    {
        if ($this->appointmentResolved) {
            return $this->resolvedAppointment;
        }

        $this->appointmentResolved = true;

        $appointmentId = $this->input('appointment_id');

        if (! is_numeric($appointmentId)) {
            return $this->resolvedAppointment = null;
        }

        // select محدود: لا نحتاج سوى الربط بالعلاج لتحديد نوع العملية.
        return $this->resolvedAppointment = Appointment::query()
            ->select(['id', 'treatment_id'])
            ->find((int) $appointmentId);
    }
}
