<?php

namespace App\Http\Controllers;

use App\Actions\Treatment\StartTreatmentAction;
use App\Exceptions\AppointmentAlreadyAttendedException;
use App\Exceptions\PendingAppointmentsException;
use App\Exceptions\TreatmentNotInProgressException;
use App\Exceptions\UnauthorizedTreatmentException;
use App\Http\Requests\CompleteTreatmentRequest;
use App\Http\Requests\StartTreatmentRequest;
use App\Http\Requests\StoreFollowUpRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\PatientHistory\PatientTreatmentHistoryResource;
use App\Http\Resources\TreatmentResource;
use App\Services\TreatmentService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class TreatmentController extends Controller
{
    protected $treatmentService;

    public function __construct(TreatmentService $treatmentService)
    {
        $this->treatmentService = $treatmentService;
    }

    public function startTreatment(StartTreatmentRequest $request, StartTreatmentAction $action)
    {
        try {
            $treatment = $action->execute($request->validated());

            return response_success(
                new TreatmentResource($treatment),
                201,
                'Treatment session started successfully.'
            );
        } catch (UnauthorizedTreatmentException | AppointmentAlreadyAttendedException $e) {
            return $e->render($request);
        } catch (Exception $e) {
            return response_error(
                $e->getMessage(),
                400,
                'Failed to start the treatment session.'
            );
        }
    }

    public function bookFollowUp(StoreFollowUpRequest $request)
    {
        try {
            $appointment = $this->treatmentService->bookFollowUpAppointment($request->validated());

            return response_success(
                new AppointmentResource($appointment),
                201,
                'Follow-up appointment has been successfully scheduled for a future date.'
            );
        } catch (\Exception $e) {
            return response_error(
                $e->getMessage(),
                400,
                'Failed to book follow-up appointment.'
            );
        }
    }

    public function completeTreatment(CompleteTreatmentRequest $request)
    {
        try {
            $treatment = $this->treatmentService->completeTreatmentFromStudent($request->validated());

            return response_success(
                new TreatmentResource($treatment),
                200,
                'Treatment completed successfully from your side. Waiting for instructor approval.'
            );
        } catch (PendingAppointmentsException | TreatmentNotInProgressException $e) {
            return $e->render($request);
        } catch (\Exception $e) {
            return response_error(
                null,
                $e->getCode() ?: 400,
                $e->getMessage()
            );
        }
    }

    public function getPatientTreatmentHistory(int $patientId)
    {
        try {
            $treatments = $this->treatmentService->getPatientTreatmentHistory($patientId);

            return response_success(
                PatientTreatmentHistoryResource::collection($treatments),
                200,
                'Patient treatment history retrieved successfully.'
            );
        } catch (ModelNotFoundException $e) {
            return response_error('Patient not found.', 404);
        } catch (\Exception $e) {
            return response_error($e->getMessage(), 400);
        }
    }

    public function rollbackStartedTreatment(int $id)
    {
        try {
            $this->treatmentService->rollbackStartedTreatment($id);

            return response_success(null, 200, 'Treatment status rolled back to cancelled, and appointment reset successfully.');
        } catch (ModelNotFoundException $e) {
            return response_error(null, 404, $e->getMessage());
        } catch (\Exception $e) {
            return response_error(null, 400, $e->getMessage());
        }
    }

    public function getProgressStats(Request $request)
    {
        try {
            $stats = $this->treatmentService->getStudentDashboardStats($request->user());

            return response_success($stats, 200, 'Student achievement stats retrieved successfully.');
        } catch (\Exception $e) {
            return response_error($e->getMessage(), 400);
        }
    }

    public function getCasesList(Request $request)
    {
        try {
            $statusType = $request->query('type', 'remaining');
            $perPage = 10;
            $cases = $this->treatmentService->getStudentCases($request->user(), $statusType, $perPage);

            return response_success($cases, 200, 'Student cases list retrieved successfully.');
        } catch (\Exception $e) {
            return response_error($e->getMessage(), 400);
        }
    }
}
