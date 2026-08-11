<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\Store\StoreStatisticService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Exception;

class StoreStatisticController extends Controller
{
    public function __construct(
        protected StoreStatisticService $statisticService
    ) {}

    public function index(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $statistics = $this->statisticService->getDashboardStatistics($user->id);

            return response_success($statistics, 200, 'تم جلب إحصائيات المتجر بنجاح.');
        } catch (Exception $e) {
            Log::error('خطأ أثناء جلب إحصائيات المتجر: ' . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي أثناء معالجة البيانات.');
        }
    }

    public function exportReport(): JsonResponse|BinaryFileResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            // إنشاء ملف التقرير مباشرة ضمن هذا الطلب (Synchronous) بدل تفويضه
            // لمهمة Job تعمل في الخلفية عبر طابور الأعمال (Queue)
            $filePath = $this->statisticService->generateSalesReportCsv($user->id);

            // لا داعي لمحاولة إرسال ملف فارغ؛ نتحقق من وجود بيانات أولاً
            // ونعيد خطأ JSON واضحاً قبل أي محاولة لإنشاء أو إرسال الملف
            if ($filePath === null) {
                return response_error(null, 404, 'لا توجد بيانات مبيعات لإنشاء التقرير.');
            }

            $fileName = 'sales_report_' . now()->format('Y_m_d_His') . '.csv';

            // إرسال الملف كاستجابة تحميل مباشرة (Stream) ضمن نفس دورة الطلب،
            // ثم حذفه فور اكتمال الإرسال لتفادي تراكم الملفات المؤقتة على الخادم
            return response()
                ->download($filePath, $fileName, ['Content-Type' => 'text/csv'])
                ->deleteFileAfterSend(true);
        } catch (Exception $e) {
            Log::error('خطأ أثناء إنشاء تقرير المبيعات: ' . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي أثناء إنشاء التقرير.');
        }
    }
}
