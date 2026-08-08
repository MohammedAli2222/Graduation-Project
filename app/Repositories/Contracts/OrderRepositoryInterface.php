<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{

    /**
     * جلب طلبات المتجر مع دعم تصفية اختيارية حسب حالة الطلب (status)، مع
     * الحفاظ على الترقيم (Pagination) القياسي — لا تُجمَّع كل الحالات أبداً
     * في استجابة واحدة ضخمة.
     *
     * @param array{status?: string} $filters
     */
    public function getStoreOrdersOptimized(int $storeId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findStoreOrder(int $storeId, int $orderId): ?Order;

    public function update(Order $order, array $data): bool;

    public function createOrder(array $orderData): Order;

    /**
     * جلب سجل المشتريات الخاص بالطالب (كمشتري).
     */
    public function getStudentPurchasesOptimized(int $studentId, int $perPage = 15): LengthAwarePaginator;

    /**
     * جلب تفاصيل طلب محدد قام الطالب بشرائه.
     */
    public function findStudentPurchase(int $studentId, int $orderId): ?Order;

    /**
     * عدد الطلبات لكل حالة (pending/processing/ready/completed/rejected) لمتجر
     * معيّن، عبر استعلام SQL مجمّع واحد (GROUP BY) بدل تنفيذ استعلام count()
     * منفصل لكل حالة.
     *
     * @return array<string, int> مثال: ['completed' => 12, 'pending' => 3, ...]
     */
    public function getOrderStatusCounts(int $storeId): array;

    /**
     * إجمالي الإيرادات ومتوسط قيمة الطلب (AOV) لمتجر معيّن، محسوبان من
     * الطلبات المكتملة (completed) فقط، عبر استعلام تجميع SQL واحد
     * (SUM/AVG) دون سحب أي صفوف إلى تطبيق PHP.
     *
     * @return array{total_revenue: float, average_order_value: float}
     */
    public function getRevenueSummary(int $storeId): array;

    /**
     * إجمالي إيراد الطلبات المكتملة ضمن نطاق تاريخي محدد (تُستخدم لحساب
     * إيراد أسبوع واحد بالتحديد ضمن استراتيجية التخزين المؤقت لكل أسبوع).
     */
    public function getRevenueBetween(int $storeId, \DateTimeInterface $from, \DateTimeInterface $to): float;

    /**
     * جلب كل الطلبات المكتملة لمتجر معيّن مع عناصرها ومنتجاتها والطالب
     * المشتري (Eager Loading كامل) لأغراض تصدير تقرير المبيعات CSV — استعلام
     * واحد للطلبات + استعلامان للعلاقات المحمَّلة مسبقاً، بلا مشكلة N+1
     * مهما كان عدد الطلبات.
     *
     * @return \Illuminate\Support\Collection<int, Order>
     */
    public function getCompletedOrdersForExport(int $storeId): \Illuminate\Support\Collection;
}
