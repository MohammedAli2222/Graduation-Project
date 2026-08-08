<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\Store\StoreStatisticService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

    public function exportReport(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $this->statisticService->triggerSalesReportExport($user->id);

            return response_success(null, 202, 'تم بدء إنشاء تقرير المبيعات، سيتم إعداده في الخلفية.');
        } catch (Exception $e) {
            Log::error('خطأ أثناء إطلاق مهمة تصدير تقرير المبيعات: ' . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي أثناء معالجة الطلب.');
        }
    }
}
