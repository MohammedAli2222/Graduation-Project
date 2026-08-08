<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Enums\OrderStatus;
use App\Jobs\ExportStoreSalesReportJob;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class StoreStatisticService
{
    private const WEEKS_IN_CHART = 8;

    private const LOW_STOCK_THRESHOLD = 5;

    private const LIVE_CACHE_MINUTES = 10;

    private const CURRENT_WEEK_CACHE_MINUTES = 5;

    private const TOP_SELLERS_CACHE_MINUTES = 60;

    public function __construct(
        protected OrderRepositoryInterface $orderRepo,
        protected ProductRepositoryInterface $productRepo,
    ) {}

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

    public function triggerSalesReportExport(int $storeId): void
    {
        ExportStoreSalesReportJob::dispatch($storeId);
    }

    public function invalidateLiveCache(int $storeId): void
    {
        Cache::tags([$this->liveTag($storeId)])->flush();
    }

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

    private function getFinancialMetrics(int $storeId): array
    {
        return Cache::tags([$this->liveTag($storeId)])->remember(
            "store_{$storeId}_revenue_summary",
            now()->addMinutes(self::LIVE_CACHE_MINUTES),
            fn () => $this->orderRepo->getRevenueSummary($storeId)
        );
    }

    private function getInventoryMetrics(int $storeId): array
    {
        $lowStockCount = Cache::tags([$this->liveTag($storeId)])->remember(
            "store_{$storeId}_low_stock_count",
            now()->addMinutes(self::LIVE_CACHE_MINUTES),
            fn () => $this->productRepo->countLowStockProducts($storeId, self::LOW_STOCK_THRESHOLD)
        );

        return ['low_stock_count' => $lowStockCount];
    }

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

    private function liveTag(int $storeId): string
    {
        return "store_{$storeId}_live_stats";
    }

    private function immutableTag(int $storeId): string
    {
        return "store_{$storeId}_weekly_immutable";
    }
}
