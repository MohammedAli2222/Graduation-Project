<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveCaseRequest;
use App\Http\Requests\DiagnoseRequest;
use App\Http\Requests\RejectCaseRequest;
use App\Http\Resources\PatientDiagnosisResource;
use App\Http\Resources\PatientResource;
use App\Services\DiagnosisService;
use App\Services\PatientService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

class InstructorController extends Controller
{
    public function __construct(protected DiagnosisService $diagnosisService, protected PatientService $patientservice) {}


    private function getInstructorId()
    {
        $id = auth()->user()->instructorProfile?->id;
        if (!$id) {
            throw new Exception('Instructor profile not found.', 404);
        }
        return $id;
    }


    public function diagnose(DiagnoseRequest $request)
    {
        try {
            $result = $this->diagnosisService->storeMultiple($request->validated(), Auth::id());

            return response_success(PatientDiagnosisResource::collection($result), 201, 'Diagnoses have been created successfully.');
        } catch (Exception $e) {
            return response_error(null, 422, $e->getMessage());
        }
    }


    public function approve(ApproveCaseRequest $request, $id)
    {
        $validatedData = $request->validated();

        try {
            $instructorId = $this->getInstructorId();

            $this->diagnosisService->approveCase(
                $id,
                $validatedData,
                auth()->id(),
                $instructorId
            );

            return response_success(null, 200, 'Case approved successfully.');
        } catch (ModelNotFoundException) {
            return response_error(null, 404, 'PatientDiagnose not found');
        } catch (Exception $e) {

            return response_error(null, $e->getCode() ?: 400, $e->getMessage());
        }
    }


    public function reject(RejectCaseRequest $request, $id)
    {

        $validatedData = $request->validated();

        try {

            $instructorId = $this->getInstructorId();


            $this->diagnosisService->rejectCase(
                $id,
                $validatedData,
                auth()->id(),
                $instructorId
            );

            return response_success(null, 200, 'Case has been rejected successfully.');
        } catch (ModelNotFoundException) {
            return response_error(null, 404, 'PatientDiagnose not found');
        } catch (Exception $e) {
            return response_error(null, 400, $e->getMessage());
        }
    }


    public function studentPending()
    {

        $instructorId = $this->getInstructorId();

        $patients = $this->patientservice->getStudentPendingPatients($instructorId);

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
