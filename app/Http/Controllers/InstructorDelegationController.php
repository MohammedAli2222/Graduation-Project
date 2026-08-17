<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\RequestDelegationRequest;
use App\Services\InstructorDelegationService;
use Exception;

class InstructorDelegationController extends Controller
{
    public function __construct(protected InstructorDelegationService $service) {}

    public function request(RequestDelegationRequest $request)
    {
        try {
            $this->service->requestDelegation(
                $request->user(),
                (int) $request->validated()['department_id']
            );

            return response_success(null, 200, 'تم إرسال طلب التفويض بنجاح، بانتظار موافقة رئيس القسم.');
        } catch (Exception $e) {
            return response_error(null, $e->getCode() ?: 400, $e->getMessage());
        }
    }
}
