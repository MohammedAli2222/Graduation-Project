<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Services\AppointmentService;
use App\Services\TreatmentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
                null,
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
}
