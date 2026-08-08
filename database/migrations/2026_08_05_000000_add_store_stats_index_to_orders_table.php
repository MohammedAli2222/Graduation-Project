<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تشغيل التهجير.
     *
     * فهرس مركّب (store_id, status, created_at) ضروري لأداء استعلامات
     * الإحصائيات: ملخص الإيرادات وإيراد الأسبوع (weekly revenue) يصفّيان
     * دوماً حسب store_id + status='completed' ثم نطاق created_at، والفهارس
     * المفردة الحالية (store_id/status كل على حدة) لا تخدم هذا النمط المركّب.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['store_id', 'status', 'created_at'], 'orders_store_status_created_idx');
        });
    }

    /**
     * التراجع عن التهجير.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_store_status_created_idx');
        });
    }
};
