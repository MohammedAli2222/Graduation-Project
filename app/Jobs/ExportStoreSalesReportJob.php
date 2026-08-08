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

/**
 * مهمة خلفية (Queue Job) تُنشئ ملف CSV لتقرير مبيعات المتجر (الطلبات
 * المكتملة فقط) وتحفظه في التخزين المحلي، دون حجب استجابة الـ API الأصلية
 * (Non-blocking I/O) — راجع StoreStatisticController::exportReport().
 */
class ExportStoreSalesReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * عدد محاولات إعادة تنفيذ المهمة عند الفشل قبل اعتبارها فاشلة نهائياً.
     */
    public int $tries = 3;

    /**
     * @param int $storeId معرف المتجر (User::id لصاحب المتجر) المطلوب تصدير تقريره
     */
    public function __construct(
        public int $storeId
    ) {}

    /**
     * تنفيذ المهمة: جلب الطلبات المكتملة عبر المستودع (وليس استعلاماً مباشراً
     * في الـ Job، حفاظاً على نمط Repository-Service)، ثم بناء ملف CSV وحفظه.
     *
     * حقن OrderRepositoryInterface هنا يتم عبر الـ Method Injection الذي
     * يدعمه Laravel تلقائياً لدالة handle() في أي Job، فلا حاجة لتمريره عبر
     * الـ Constructor (وبالتالي لا حاجة لتسلسله/Serialization مع المهمة).
     */
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

    /**
     * بناء محتوى ملف CSV يدوياً عبر fputcsv (لا توجد حزمة تصدير جاهزة مثل
     * maatwebsite/excel أو league/csv مثبّتة حالياً في composer.json)، مع سطر
     * واحد لكل عنصر طلب (Order Item) لتفاصيل أدق في التقرير.
     */
    private function buildCsvContent(Collection $orders): string
    {
        $handle = fopen('php://temp', 'r+');

        // BOM لضمان عرض الأحرف العربية بشكل صحيح عند فتح الملف في Excel
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
