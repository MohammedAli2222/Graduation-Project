<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserDeviceToken;
use Illuminate\Database\Seeder;

/**
 * يولّد توكنات أجهزة (FCM Device Tokens) واقعية للمستخدمين لتفعيل اختبار
 * الإشعارات الفورية. لا يحصل كل مستخدم على توكن (كما في الواقع، ليس كل
 * المستخدمين يمنحون إذن الإشعارات أو يسجّلون الدخول من التطبيق)، وبعضهم
 * يملك أكثر من جهاز واحد (هاتف + جهاز لوحي مثلاً).
 */
class NotificationSeeder extends Seeder
{
    /** نسبة المستخدمين الذين لديهم توكن إشعارات مسجّل */
    private const ADOPTION_PERCENT = 70;

    public function run(): void
    {
        $userIds = User::pluck('id');

        if ($userIds->isEmpty()) {
            $this->command?->warn('لا يوجد مستخدمون بعد لإنشاء توكنات الإشعارات لهم.');

            return;
        }

        $this->command?->withProgressBar($userIds, function (int $userId): void {
            if (random_int(1, 100) > self::ADOPTION_PERCENT) {
                return;
            }

            // بعض المستخدمين يسجّلون الدخول من أكثر من جهاز واحد (تعدد الأجهزة)
            $devicesCount = random_int(1, 100) <= 20 ? 2 : 1;

            UserDeviceToken::factory()->count($devicesCount)->create([
                'user_id' => $userId,
            ]);
        });

        $this->command?->newLine(2);
    }
}
