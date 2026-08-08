<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Patient;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * معالجة صور المريض في الخلفية: نقلها من القرص المؤقت إلى مكتبة الوسائط
 * وتوليد تحويلاتها، حتى تبقى استجابة شاشة الاستقبال فورية.
 *
 * قاعدة إدارة الملفات المؤقتة (Idempotency):
 * الملفات المؤقتة هي *مصدر البيانات الوحيد* لهذه المهمة، فحذفها قبل نجاحها
 * يجعل كل محاولة إعادة لاحقة بلا مدخلات فتفشل نهائياً وتضيع صور المريض.
 * لذلك لا تُحذف إلا في ثلاث حالات فقط:
 * 1) اكتمال المعالجة بنجاح.
 * 2) عدم وجود المريض أصلاً (لا مالك للملفات).
 * 3) استنفاد كل المحاولات عبر failed() — تنظيف نهائي يمنع تراكم ملفات يتيمة.
 */
class ProcessPatientImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param  array<string, string|array<int, string>>  $tempImages مفاتيحه أسماء مجموعات
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
            // لا مالك للملفات: إعادة المحاولة لن تُنشئ المريض، فالتنظيف نهائي هنا.
            $this->cleanupTempFiles();

            return;
        }

        foreach ($this->tempImages as $collection => $paths) {
            foreach ((array) $paths as $path) {
                if (! Storage::disk('local')->exists($path)) {
                    // الملف غير موجود: إمّا عولج في محاولة سابقة نجحت جزئياً،
                    // أو حُذف يدوياً. نتخطّاه بدل إفشال المهمة كلها.
                    continue;
                }

                // addMedia هي الخطوة المكلفة فعلياً (نقل الملف لقرص public
                // + توليد التحويلات)، وكانت سابقاً تُنفَّذ داخل الطلب نفسه.
                $patient->addMedia(Storage::disk('local')->path($path))
                    ->toMediaCollection($collection);
            }
        }

        // لا يُحذف شيء إلا بعد اكتمال كل المجموعات بنجاح: أي استثناء أعلاه
        // يترك الملفات سليمة لتلتقطها المحاولة التالية.
        $this->cleanupTempFiles();
    }

    /**
     * تُستدعى من لارافيل بعد استنفاد كل المحاولات ($tries).
     *
     * هنا فقط نتخلّص من الملفات المؤقتة نهائياً، وإلا بقيت على القرص للأبد
     * بلا أي مهمة تستهلكها. نسجّل الفشل مع مسارات الملفات قبل حذفها ليبقى
     * أثرٌ يمكن التحقيق فيه.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('فشلت معالجة صور المريض نهائياً بعد استنفاد المحاولات.', [
            'patient_id' => $this->patientId,
            'temp_images' => $this->tempImages,
            'exception' => $exception?->getMessage(),
        ]);

        $this->cleanupTempFiles();
    }

    protected function cleanupTempFiles(): void
    {
        foreach ($this->tempImages as $paths) {
            foreach ((array) $paths as $path) {
                try {
                    if (Storage::disk('local')->exists($path)) {
                        Storage::disk('local')->delete($path);
                    }
                } catch (Exception $e) {
                    // فشل حذف ملف مؤقت لا يجوز أن يُفشل مهمة نجحت معالجتها،
                    // ولا أن يمنع تنظيف بقية الملفات.
                    Log::warning('تعذّر حذف ملف مؤقت لصور المريض.', [
                        'patient_id' => $this->patientId,
                        'path' => $path,
                        'exception' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
