<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProcessPatientImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param array<string, string|array<int, string>> $tempImages مفاتيحه أسماء مجموعات
     * الوسائط في نموذج Patient (id_cards, clinical_images, x_ray_images)، وقيمته
     * مسار مؤقت واحد (لـ id_cards) أو مصفوفة مسارات مؤقتة على قرص local
     */
    public function __construct(
        public int $patientId,
        public array $tempImages
    ) {}

    public function handle(): void
    {
        $patient = Patient::find($this->patientId);

        if (! $patient) {
            $this->cleanupTempFiles();
            return;
        }

        try {
            foreach ($this->tempImages as $collection => $paths) {
                foreach ((array) $paths as $path) {
                    if (Storage::disk('local')->exists($path)) {
                        // addMedia هنا هي الخطوة المكلفة فعلياً (نقل الملف لقرص public
                        // + توليد تحويل thumb)، والتي كانت سابقاً تُنفَّذ أثناء الطلب
                        // نفسه وتُبطئ استجابة شاشة الاستقبال؛ الآن تُنفَّذ في الخلفية.
                        // لا يوجد عمود "رابط صورة" منفصل على جدول patients لتحديثه:
                        // toMediaCollection() هي نفسها آلية "ربط" الصورة بسجل المريض
                        // عبر جدول media متعدد الأشكال (polymorphic) التابع لـ Spatie
                        $patient->addMedia(Storage::disk('local')->path($path))
                            ->toMediaCollection($collection);

                        // نقطة تمديد اختيارية: لو أُضيفت مكتبة intervention/image
                        // مستقبلاً، هذا هو المكان المناسب لضغط/تصغير الصورة قبل
                        // toMediaCollection() دون التأثير على زمن استجابة الطلب الأصلي
                    }
                }
            }
        } catch (Exception $e) {
            Log::error("خطأ أثناء معالجة صور المريض رقم {$this->patientId}: " . $e->getMessage());
        } finally {
            $this->cleanupTempFiles();
        }
    }

    protected function cleanupTempFiles(): void
    {
        foreach ($this->tempImages as $paths) {
            foreach ((array) $paths as $path) {
                if (Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->delete($path);
                }
            }
        }
    }
}
