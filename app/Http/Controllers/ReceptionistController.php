<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Resources\PatientResource;
use App\Services\PatientService;

class ReceptionistController extends Controller
{
    protected PatientService $patientService;

    public function __construct(PatientService $patientService)
    {
        $this->patientService = $patientService;
    }

    public function store(StorePatientRequest $request)
    {
        try {
            $patient = $this->patientService->registerPatient(
                $request->validated(),
                $request->file('images')
            );

            return response_success(new PatientResource($patient), 201, 'Created patient successfully.');
        } catch (\Exception $e) {
            return response_error(
                null,
                500,
                'Something went wrong: ' . $e->getMessage()
            );
        }
    }
}
