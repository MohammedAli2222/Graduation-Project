<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Enums\OrderStatus;
use App\Jobs\ExportStoreSalesReportJob;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * خدمة إحصائيات لوحة تحكم صاحب المتجر. تُطبِّق إستراتيجية تخزين مؤقت متقدمة
 * ومتمايزة حسب "مدى تقلّب" كل بيان: بيانات حيّة (تتغير مع كل طلب جديد) مقابل
 * بيانات أسابيع ماضية ثابتة (immutable) لا يمكن أن تتغير مستقبلاً إطلاقاً.
 */
class StoreStatisticService
{
    // عدد الأسابيع المعروضة في مخطط الإيراد الأسبوعي (شاملاً الأسبوع الحالي)
    private const WEEKS_IN_CHART = 8;

    // الحد الأدنى للكمية الذي يُعتبر تحته المنتج "منخفض المخزون"
    private const LOW_STOCK_THRESHOLD = 5;

    // مدة تخزين مؤقت للبيانات "الحية" (عدد الطلبات، الإيرادات، المخزون)
    // التي تتغير مع كل طلب جديد أو مكتمل
    private const LIVE_CACHE_MINUTES = 10;

    // مدة أقصر خصيصاً لإيراد الأسبوع الحالي: هذا الأسبوع لا يزال "مفتوحاً"
    // وقد تكتمل طلبات جديدة ضمنه في أي لحظة، على عكس الأسابيع المنتهية
    private const CURRENT_WEEK_CACHE_MINUTES = 5;

    // استعلام أفضل المنتجات مبيعاً أثقل نسبياً (JOIN مزدوج + GROUP BY)
    // فيستحق مدة تخزين مؤقت أطول من بقية البيانات الحية
    private const TOP_SELLERS_CACHE_MINUTES = 60;

    public function __construct(
        protected OrderRepositoryInterface $orderRepo,
        protected ProductRepositoryInterface $productRepo,
    ) {}

    /**
     * جلب لوحة إحصائيات المتجر الكاملة. كل قسم مُخزَّن مؤقتاً بشكل مستقل
     * وبإستراتيجية TTL مختلفة حسب طبيعة بياناته (راجع تعليقات كل دالة أدناه).
     */
    public function getDashboardStatistics(int $storeId): array
    {
        return [
            'orders' => $this->getOrderMetrics($storeId),
            'financials' => $this->getFinancialMetrics($storeId),
            'inventory' => $this->getInventoryMetrics($storeId),
            'top_sellers' => $this->getTopSellers($storeId),
            'weekly_revenue_chart' => $this->getWeeklyRevenueChart($storeId),
        ];
    }

    /**
     * إطلاق مهمة تصدير تقرير المبيعات في الخلفية (Queue) والعودة فوراً —
     * الـ Controller لا ينتظر انتهاء المهمة إطلاقاً (Non-blocking I/O).
     */
    public function triggerSalesReportExport(int $storeId): void
    {
        ExportStoreSalesReportJob::dispatch($storeId);
    }

    /**
     * إبطال (Invalidate) كل التخزين المؤقت "الحي" لمتجر معيّن. يجب استدعاؤها
     * فور أي عملية تُغيّر حالة طلب أو كمية منتج (مثال جاهز: تم ربطها في
     * StoreOrderService::updateOrderStatus() بعد كل تحديث ناجح لحالة الطلب).
     *
     * لا تُبطل عمداً بيانات الأسابيع الماضية الثابتة (وسم منفصل تماماً)،
     * لأن اكتمال طلب اليوم لا يُغيّر أبداً إيراد أسبوع مضى وانتهى فعلياً.
     */
    public function invalidateLiveCache(int $storeId): void
    {
        Cache::tags([$this->liveTag($storeId)])->flush();
    }

    /**
     * عدد الطلبات لكل حالة + الإجمالي، عبر استعلام SQL مجمّع واحد (GROUP BY)
     * مُخزَّن مؤقتاً لأنه يفحص جدول orders بالكامل لمتجر معيّن.
     */
    private function getOrderMetrics(int $storeId): array
    {
        $counts = Cache::tags([$this->liveTag($storeId)])->remember(
            "store_{$storeId}_order_status_counts",
            now()->addMinutes(self::LIVE_CACHE_MINUTES),
            fn () => $this->orderRepo->getOrderStatusCounts($storeId)
        );

        return [
            'total' => array_sum($counts),
            'pending' => $counts[OrderStatus::PENDING->value] ?? 0,
            'processing' => $counts[OrderStatus::PROCESSING->value] ?? 0,
            'ready' => $counts[OrderStatus::READY->value] ?? 0,
            'completed' => $counts[OrderStatus::COMPLETED->value] ?? 0,
            'rejected' => $counts[OrderStatus::REJECTED->value] ?? 0,
        ];
    }

    /**
     * إجمالي الإيرادات ومتوسط قيمة الطلب (AOV)، من الطلبات المكتملة فقط،
     * عبر استعلام SUM/AVG واحد مُخزَّن مؤقتاً.
     */
    private function getFinancialMetrics(int $storeId): array
    {
        return Cache::tags([$this->liveTag($storeId)])->remember(
            "store_{$storeId}_revenue_summary",
            now()->addMinutes(self::LIVE_CACHE_MINUTES),
            fn () => $this->orderRepo->getRevenueSummary($storeId)
        );
    }

    /**
     * عدد المنتجات منخفضة المخزون (quantity < 5)، مُخزَّن مؤقتاً لتفادي
     * إعادة فحص جدول المنتجات بالكامل مع كل تحميل للوحة التحكم.
     */
    private function getInventoryMetrics(int $storeId): array
    {
        $lowStockCount = Cache::tags([$this->liveTag($storeId)])->remember(
            "store_{$storeId}_low_stock_count",
            now()->addMinutes(self::LIVE_CACHE_MINUTES),
            fn () => $this->productRepo->countLowStockProducts($storeId, self::LOW_STOCK_THRESHOLD)
        );

        return ['low_stock_count' => $lowStockCount];
    }

    /**
     * أفضل 5 منتجات مبيعاً — استعلام JOIN مزدوج نسبياً "ثقيل"، لذا يُخزَّن
     * مؤقتاً لمدة أطول (ساعة) من بقية البيانات الحية. القيمة المُعادة مصفوفة
     * عادية (وليست Eloquent Collection) عمداً لتبقى بيانات التخزين المؤقت
     * خفيفة وبسيطة التسلسل (Serialization) عبر Redis.
     */
    private function getTopSellers(int $storeId): array
    {
        return Cache::tags([$this->liveTag($storeId)])->remember(
            "store_{$storeId}_top_sellers",
            now()->addMinutes(self::TOP_SELLERS_CACHE_MINUTES),
            function () use ($storeId) {
                return $this->productRepo->getTopSellingProducts($storeId, 5)
                    ->map(fn ($product) => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => (float) $product->price,
                        'stock_quantity' => (int) $product->stock_quantity,
                        'total_sold' => (int) $product->total_sold,
                    ])
                    ->values()
                    ->all();
            }
        );
    }

    /**
     * *** إستراتيجية التخزين المؤقت المتقدمة (النقطة الحرجة في المتطلبات) ***
     *
     * لكل أسبوع من الأسابيع الثمانية الماضية:
     * - إن كان أسبوعاً "منتهياً" (وليس الأسبوع الحالي): إيراده رقم ثابت لن
     *   يتغيّر أبداً في المستقبل (لا يمكن لأي طلب أن "يكتمل" بأثر رجعي ضمن
     *   أسبوع انتهى فعلياً) → يُخزَّن مؤقتاً بشكل دائم (rememberForever)
     *   بمفتاح فريد لكل أسبوع (يتضمن تاريخ بدايته)، تحت وسم Immutable منفصل
     *   تماماً لا يمسّه invalidateLiveCache() أبداً. بعد أول حساب له لن يُعاد
     *   الاستعلام من قاعدة البيانات مرة أخرى إطلاقاً.
     * - أما الأسبوع الحالي (لا يزال مفتوحاً لاستقبال طلبات مكتملة جديدة في
     *   أي لحظة) فيُخزَّن مؤقتاً لمدة قصيرة جداً (5 دقائق) فقط، تحت وسم
     *   البيانات الحية القابل للإبطال.
     */
    private function getWeeklyRevenueChart(int $storeId): array
    {
        $weeks = [];
        $now = Carbon::now();

        for ($i = self::WEEKS_IN_CHART - 1; $i >= 0; $i--) {
            $weekStart = $now->copy()->subWeeks($i)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();
            $isCurrentWeek = $i === 0;

            $cacheKey = "store_{$storeId}_weekly_revenue_{$weekStart->format('Y_m_d')}";

            if ($isCurrentWeek) {
                $revenue = Cache::tags([$this->liveTag($storeId)])->remember(
                    $cacheKey,
                    now()->addMinutes(self::CURRENT_WEEK_CACHE_MINUTES),
                    fn () => $this->orderRepo->getRevenueBetween($storeId, $weekStart, $weekEnd)
                );
            } else {
                $revenue = Cache::tags([$this->immutableTag($storeId)])->rememberForever(
                    $cacheKey,
                    fn () => $this->orderRepo->getRevenueBetween($storeId, $weekStart, $weekEnd)
                );
            }

            $weeks[] = [
                'week_start' => $weekStart->format('Y-m-d'),
                'week_end' => $weekEnd->format('Y-m-d'),
                'revenue' => round((float) $revenue, 2),
                'is_current_week' => $isCurrentWeek,
            ];
        }

        return $weeks;
    }

    /**
     * وسم (Tag) البيانات "الحية" المتقلبة لمتجر معيّن — يُستخدم كوحدة إبطال
     * واحدة عبر invalidateLiveCache(). مُعرَّف بمعزل تام عن وسم البيانات
     * الثابتة كي لا يتأثر أحدهما بإبطال الآخر.
     */
    private function liveTag(int $storeId): string
    {
        return "store_{$storeId}_live_stats";
    }

    /**
     * وسم منفصل تماماً لإيراد الأسابيع المنتهية الثابتة — لا يُستدعى عليه
     * flush() إطلاقاً من أي عملية تحديث عادية في التطبيق، فهذه البيانات
     * لا "تنتهي صلاحيتها" منطقياً أبداً.
     */
    private function immutableTag(int $storeId): string
    {
        return "store_{$storeId}_weekly_immutable";
    }
}
