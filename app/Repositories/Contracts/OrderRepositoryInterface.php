<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{

    public function getStoreOrdersOptimized(int $storeId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findStoreOrder(int $storeId, int $orderId): ?Order;

    public function update(Order $order, array $data): bool;

    public function createOrder(array $orderData): Order;

    public function getStudentPurchasesOptimized(int $studentId, int $perPage = 15): LengthAwarePaginator;

    public function findStudentPurchase(int $studentId, int $orderId): ?Order;

    public function getOrderStatusCounts(int $storeId): array;

    public function getRevenueSummary(int $storeId): array;

    public function getRevenueBetween(int $storeId, \DateTimeInterface $from, \DateTimeInterface $to): float;

    public function getCompletedOrdersForExport(int $storeId): \Illuminate\Support\Collection;
}
