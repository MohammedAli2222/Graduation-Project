<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class ExportStoreSalesReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $storeId
    ) {}

    public function handle(OrderRepositoryInterface $orderRepo): void
    {
        try {
            $orders = $orderRepo->getCompletedOrdersForExport($this->storeId);

            $csvContent = $this->buildCsvContent($orders);

            $relativePath = sprintf(
                'exports/store_%d/sales_report_%s.csv',
                $this->storeId,
                now()->format('Y_m_d_His')
            );

            Storage::disk('local')->put($relativePath, $csvContent);

            Log::info("تم إنشاء تقرير المبيعات للمتجر رقم {$this->storeId} بنجاح في المسار: {$relativePath}");
        } catch (Exception $e) {
            Log::error("خطأ أثناء إنشاء تقرير المبيعات للمتجر رقم {$this->storeId}: " . $e->getMessage());
        }
    }

    private function buildCsvContent(Collection $orders): string
    {
        $handle = fopen('php://temp', 'r+');

        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, [
            'رقم الطلب',
            'تاريخ الطلب',
            'اسم العميل',
            'اسم المنتج',
            'الكمية',
            'سعر الوحدة',
            'الإجمالي الفرعي',
            'إجمالي الطلب',
        ]);

        /** @var Order $order */
        foreach ($orders as $order) {
            $customerName = trim(($order->student->first_name ?? '') . ' ' . ($order->student->last_name ?? ''));
            $customerName = $customerName !== '' ? $customerName : 'غير معروف';

            foreach ($order->orderItems as $item) {
                fputcsv($handle, [
                    $order->id,
                    $order->created_at->format('Y-m-d H:i'),
                    $customerName,
                    $item->product->name ?? 'منتج محذوف',
                    $item->quantity,
                    number_format((float) $item->unit_price, 2, '.', ''),
                    number_format((float) $item->subtotal, 2, '.', ''),
                    number_format((float) $order->total_amount, 2, '.', ''),
                ]);
            }
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
