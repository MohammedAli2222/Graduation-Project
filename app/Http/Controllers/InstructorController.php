<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DiagnoseRequest;
use App\Http\Resources\PatientDiagnosisResource;
use App\Services\DiagnosisService;
use Illuminate\Support\Facades\Auth;

class InstructorController extends Controller
{
    public function __construct(protected DiagnosisService $diagnosisService) {}

    public function diagnose(DiagnoseRequest $request)
    {
        $result = $this->diagnosisService->storeMultiple($request->validated(), Auth::id());

        return response_success(PatientDiagnosisResource::collection($result) ,201 ,'Diagnoses have been created successfully.');

    }
}
