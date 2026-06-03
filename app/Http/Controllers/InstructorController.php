<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DiagnoseRequest;
use App\Http\Resources\PatientDiagnosisResource;
use App\Http\Resources\PatientResource;
use App\Services\DiagnosisService;
use App\Services\PatientService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InstructorController extends Controller
{
    public function __construct(protected DiagnosisService $diagnosisService, protected PatientService $patientservice) {}

    public function diagnose(DiagnoseRequest $request)
    {
        try {
            $result = $this->diagnosisService->storeMultiple($request->validated(), Auth::id());

            return response_success(PatientDiagnosisResource::collection($result), 201, 'Diagnoses have been created successfully.');
        } catch (Exception $e) {
            return response_error(null, 422, $e->getMessage());
        }
    }


    public function approve(Request $request, $id)
    {
        $request->validate([
            'final_diagnosis' => 'required|string|max:1000',
        ]);

        try {
            $instructorProfileId = auth()->user()->instructorProfile?->id;

            if (!$instructorProfileId) {
                return response_error('Instructor profile not found.', 404);
            }

            $this->diagnosisService->approveCase(
                $id,
                $request->final_diagnosis,
                auth()->id(),
                $instructorProfileId
            );

            return response_success(null, 200, 'Case approved successfully.');
        } catch (Exception $e) {

            return response_error(null, 400, $e->getMessage());
        }
    }


    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        try {
            
            $instructorProfileId = auth()->user()->instructorProfile?->id;

            if (!$instructorProfileId) {
                return response_error('Instructor profile not found.', 404);
            }


            $this->diagnosisService->rejectCase(
                $id,
                $request->rejection_reason,
                auth()->id(),
                $instructorProfileId
            );

            return response_success(null, 200, 'Case has been rejected successfully.');
        } catch (Exception $e) {
            return response_error(null, 400, $e->getMessage());
        }
    }


    public function studentPending()
    {

        $instructorProfileId = auth()->user()->instructorProfile?->id;

        if (!$instructorProfileId) {
            return response_error('Instructor profile not found.', 404);
        }

        $patients = $this->patientservice->getStudentPendingPatients($instructorProfileId);

        if ($patients->isEmpty()) {
            return response_success([], 200, 'Waiting list is currently empty.');
        }

        return response_success([
            'patients' => PatientResource::collection($patients->items()),
            'pagination' => [
                'total'        => $patients->total(),
                'count'        => $patients->count(),
                'per_page'     => $patients->perPage(),
                'current_page' => $patients->currentPage(),
                'last_page'    => $patients->lastPage(),
            ]
        ], 200, 'Student pending requests fetched successfully.');
    }
}
