<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use App\Services\TreatmentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    protected $appointmentService;

    protected $treatmentService;

    public function __construct(AppointmentService $appointmentService, TreatmentService $treatmentService)
    {
        $this->appointmentService = $appointmentService;
        $this->treatmentService = $treatmentService;
    }

    public function bookCase(StoreAppointmentRequest $request)
    {
        try {
            $appointments = $this->appointmentService->reserveCase($request->validated());

            foreach ($appointments as $appointment) {
                $appointment->load('patient');
            }

            return response_success(
                AppointmentResource::collection(collect($appointments)),
                201,
                'The case has been successfully reserved and the appointment scheduled.'
            );
        } catch (ValidationException $e) {
            return response_error(
                $e->errors(),
                422,
                'Academic validation requirements failed.'
            );
        } catch (\Exception $e) {
            return response_error(
                ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()],
                500,
                'An unexpected error occurred during the booking process.'
            );
        }
    }

    public function cancelAppointment(int $id)
    {
        try {
            $this->treatmentService->cancelAppointment($id);

            return response_success(null, 200, 'Appointment cancelled successfully before attendance.');
        } catch (ModelNotFoundException $e) {
            return response_error(null, 404, $e->getMessage());
        } catch (\Exception $e) {
            return response_error(null, 400, $e->getMessage());
        }
    }

    public function getMyAppointments()
    {

        try {
            $studentId = auth()->id();
            $appointments = $this->appointmentService->getStudentAppointments($studentId);

            return response_success(
                [
                    'data' => AppointmentResource::collection($appointments),
                    'pagination' => [
                        'total' => $appointments->total(),
                        'current_page' => $appointments->currentPage(),
                        'last_page' => $appointments->lastPage(),
                    ],
                ],
                200,
                'Appointments retrieved successfully.'
            );
        } catch (\Exception $e) {
            return response_error(null, 500, 'Error retrieving appointments: ' . $e->getMessage());
        }
    }

    public function getAppointmentHistory()
    {
        try {
            $studentId = auth()->id();
            $history = $this->appointmentService->getStudentAppointmentHistory($studentId);

            return response_success(
                [
                    'data' => AppointmentResource::collection($history),
                    'pagination' => [
                        'total' => $history->total(),
                        'current_page' => $history->currentPage(),
                    ],
                ],
                200,
                'Appointment history retrieved successfully.'
            );
        } catch (\Exception $e) {
            return response_error(null, 500, 'Error retrieving history: ' . $e->getMessage());
        }
    }
}
