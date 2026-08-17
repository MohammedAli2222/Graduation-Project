<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * الاسم user_notifications عمداً وليس notifications: لارافيل يحجز اسم
     * الجدول notifications لقناة database الخاصة بـ Notifiable (مفتاح UUID،
     * أعمدة polymorphic اسمها notifiable_type/notifiable_id، وعمود type يخزّن
     * اسم صف الإشعار الكامل). جدولنا هذا بمخطط مختلف تماماً (مفتاح تسلسلي،
     * user_id مباشر، type نص قصير مخصّص)، فاستخدام نفس الاسم كان سيصطدم لاحقاً
     * لو استُخدمت قناة database الافتراضية على User (التي تستخدم حالياً قناة
     * mail فقط عبر SendOtpNotification).
     */
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->text('body');

            // نفس قيمة data['type'] المُرسَلة أصلاً إلى Firebase في كل Listener
            // (مثال: treatment_reviewed, new_cases_available)
            $table->string('type')->index();

            // نفس حمولة data المرسَلة إلى FCM، تُخزَّن أيضاً هنا حتى تستطيع
            // الواجهة إعادة بناء نفس التنقّل العميق (Deep Link) من شاشة السجل.
            $table->json('data')->nullable();

            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // استعلام عدّاد غير المقروء يُنفَّذ عند كل فتح للتطبيق، فهرس مركّب
            // مخصّص له بدل الاعتماد على فهرس user_id وحده.
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
