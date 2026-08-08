<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\PatientMedicalHistory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * يُنشئ 500 مريض واقعي مع تاريخهم الطبي، وصورة هوية توضيحية (Placeholder)
 * لمعظمهم لمحاكاة الملفات المرفوعة فعلياً عبر واجهة الاستقبال.
 */
class PatientSeeder extends Seeder
{
    private const TOTAL_PATIENTS = 500;

    /** نسبة المرضى الذين يُسجَّلون عبر موظف الاستقبال مقابل تسجيل مباشر من طالب */
    private const RECEPTIONIST_SHARE_PERCENT = 30;

    /** نسبة المرضى الذين تُرفق لهم صورة بطاقة هوية توضيحية */
    private const MEDIA_ATTACH_PERCENT = 80;

    public function run(): void
    {
        $receptionistIds = User::role('receptionist')->pluck('id');
        $studentIds = User::role('student')->pluck('id');

        if ($receptionistIds->isEmpty() && $studentIds->isEmpty()) {
            $this->command?->warn('يرجى تشغيل UserSeeder أولاً لوجود مستخدمين (استقبال/طلاب) قبل توليد المرضى.');

            return;
        }

        $placeholderImagePath = $this->preparePlaceholderImage();

        $this->command?->withProgressBar(range(1, self::TOTAL_PATIENTS), function () use ($receptionistIds, $studentIds, $placeholderImagePath): void {
            // 30% من المرضى يُسجَّلون من قبل موظف استقبال، والباقي من قبل طالب يقترح حالتهم مباشرة
            $useReceptionist = $receptionistIds->isNotEmpty()
                && ($studentIds->isEmpty() || random_int(1, 100) <= self::RECEPTIONIST_SHARE_PERCENT);

            $addedBy = $useReceptionist ? $receptionistIds->random() : $studentIds->random();

            $patient = Patient::factory()->create([
                'added_by' => $addedBy,
            ]);

            $isFemale = $patient->gender === 'female';
            $isPregnant = $isFemale && random_int(1, 100) <= 10;

            PatientMedicalHistory::factory()->create([
                'patient_id' => $patient->id,
                'is_pregnant' => $isFemale ? $isPregnant : null,
            ]);

            if (random_int(1, 100) <= self::MEDIA_ATTACH_PERCENT) {
                $patient->addMedia($placeholderImagePath)
                    ->preservingOriginal()
                    ->toMediaCollection('id_cards');
            }
        });

        $this->command?->newLine(2);
    }

    /**
     * ننشئ صورة JPEG بسيطة (باستخدام GD) لمرة واحدة فقط ونعيد استخدام نفس
     * الملف كصورة توضيحية (Placeholder) لكل المرضى، بدلاً من تنزيل صور
     * حقيقية من الإنترنت أثناء الـ Seeding (أسرع وأكثر استقراراً بلا اتصال شبكي).
     */
    private function preparePlaceholderImage(): string
    {
        $directory = storage_path('app/seeders-tmp');
        File::ensureDirectoryExists($directory);

        $path = $directory . DIRECTORY_SEPARATOR . 'patient_placeholder.jpg';

        if (! File::exists($path)) {
            $image = imagecreatetruecolor(300, 300);
            $background = imagecolorallocate($image, 224, 234, 245);
            $textColor = imagecolorallocate($image, 60, 90, 130);
            imagefill($image, 0, 0, $background);
            imagestring($image, 5, 90, 140, 'ID CARD', $textColor);
            imagejpeg($image, $path, 85);
            imagedestroy($image);
        }

        return $path;
    }
}
