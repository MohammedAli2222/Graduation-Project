<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * حقن نموذج الطلب.
     */
    public function __construct(
        protected Order $model
    ) {}



    public function getStoreOrdersOptimized(int $storeId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('store_id', $storeId)
            // تصفية اختيارية حسب حالة الطلب (pending/processing/ready/completed/rejected)
            // لدعم الواجهة ذات التبويبات في تطبيق Flutter دون كسر الترقيم
            ->when(isset($filters['status']), function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            // إضافة student.studentProfile
            ->with(['student.studentProfile', 'orderItems.product:id,name'])
            ->latest()
            ->paginate($perPage);
    }

    public function findStoreOrder(int $storeId, int $orderId): ?Order
    {
        return $this->model->newQuery()
            ->where('store_id', $storeId)
            ->where('id', $orderId)
            ->with(['student.studentProfile', 'orderItems.product:id,name'])
            ->first();
    }


    public function update(Order $order, array $data): bool
    {
        return $order->update($data);
    }

    public function createOrder(array $orderData): Order
    {
        return $this->model->create($orderData);
    }

    public function getStudentPurchasesOptimized(int $studentId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('student_id', $studentId)
            ->with(['store.storeProfile', 'store.studentProfile', 'orderItems.product:id,name'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * جلب تفاصيل طلب محدد ضمن مشتريات الطالب.
     */
    public function findStudentPurchase(int $studentId, int $orderId): ?Order
    {
        return $this->model->newQuery()
            ->where('student_id', $studentId)
            ->where('id', $orderId)
            ->with(['store.storeProfile', 'store.studentProfile', 'orderItems.product:id,name'])
            ->first();
    }

    /**
     * عدد الطلبات لكل حالة عبر استعلام SQL مجمّع واحد (GROUP BY status)
     * بدل 5 استعلامات count() منفصلة لكل حالة من حالات OrderStatus.
     */
    public function getOrderStatusCounts(int $storeId): array
    {
        return $this->model->newQuery()
            ->where('store_id', $storeId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            // pluck على Eloquent Builder يستخدم استعلام SQL خام دون تهيئة
            // نماذج Eloquent كاملة، لذا القيم المُعادة نصوص خام (لا Enum Cast)
            ->pluck('total', 'status')
            ->toArray();
    }

    /**
     * إجمالي الإيرادات ومتوسط قيمة الطلب من الطلبات المكتملة فقط، عبر
     * استعلام SUM/AVG واحد ينفَّذ بالكامل داخل قاعدة البيانات.
     */
    public function getRevenueSummary(int $storeId): array
    {
        $result = $this->model->newQuery()
            ->where('store_id', $storeId)
            ->where('status', OrderStatus::COMPLETED->value)
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_revenue, COALESCE(AVG(total_amount), 0) as average_order_value')
            ->first();

        return [
            'total_revenue' => (float) ($result->total_revenue ?? 0),
            'average_order_value' => (float) ($result->average_order_value ?? 0),
        ];
    }

    /**
     * إيراد الطلبات المكتملة ضمن نطاق تاريخي محدد (تُستخدم لحساب أسبوع واحد
     * فقط ضمن مخطط الإيراد الأسبوعي في الخدمة). يعتمد على الفهرس المركّب
     * (store_id, status, created_at) المضاف في مايجريشن add_store_stats_index.
     */
    public function getRevenueBetween(int $storeId, \DateTimeInterface $from, \DateTimeInterface $to): float
    {
        return (float) $this->model->newQuery()
            ->where('store_id', $storeId)
            ->where('status', OrderStatus::COMPLETED->value)
            ->whereBetween('created_at', [$from, $to])
            ->sum('total_amount');
    }

    /**
     * كل الطلبات المكتملة لمتجر معيّن مع تحميل مسبق كامل للعلاقات اللازمة
     * لتصدير تقرير CSV (الطالب المشتري + عناصر الطلب + المنتجات)، لتفادي
     * أي استعلام N+1 داخل الحلقة عند بناء التقرير في الـ Job.
     */
    public function getCompletedOrdersForExport(int $storeId): \Illuminate\Support\Collection
    {
        return $this->model->newQuery()
            ->where('store_id', $storeId)
            ->where('status', OrderStatus::COMPLETED->value)
            ->with([
                'student:id,first_name,last_name',
                'orderItems.product:id,name',
            ])
            ->orderBy('created_at')
            ->get();
    }
}
