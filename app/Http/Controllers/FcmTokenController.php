<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\Contracts\FcmTokenRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FcmTokenController extends Controller
{
    public function __construct(protected FcmTokenRepositoryInterface $fcmTokenRepo) {}

    // حفظ أو تحديث توكن الجهاز الخاص بالمستخدم المصادق عليه لاستقبال إشعارات Firebase
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'fcm_token' => ['required', 'string'],
                'device_type' => ['nullable', 'string', 'in:android,ios,web'],
            ]);

            $this->fcmTokenRepo->storeToken(
                auth()->id(),
                $validated['fcm_token'],
                $validated['device_type'] ?? null
            );

            return response_success(null, 200, 'تم حفظ توكن الإشعارات بنجاح.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response_error($e->errors(), 422, 'بيانات غير صالحة.');
        } catch (\Exception $e) {
            return response_error(null, 500, 'حدث خطأ أثناء حفظ توكن الإشعارات: ' . $e->getMessage());
        }
    }

    // إزالة توكن الجهاز عند تسجيل الخروج، حتى لا يستمر المستخدم التالي الذي
    // يسجّل دخوله على نفس الجهاز باستقبال إشعارات المستخدم السابق
    public function destroy(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'fcm_token' => ['required', 'string'],
            ]);

            $this->fcmTokenRepo->deleteTokenForUser(auth()->id(), $validated['fcm_token']);

            return response_success(null, 200, 'تم حذف توكن الإشعارات بنجاح.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response_error($e->errors(), 422, 'بيانات غير صالحة.');
        } catch (\Exception $e) {
            return response_error(null, 500, 'حدث خطأ أثناء حذف توكن الإشعارات: ' . $e->getMessage());
        }
    }
}
