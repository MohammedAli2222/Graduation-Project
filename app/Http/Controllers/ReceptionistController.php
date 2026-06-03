<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Services\PatientService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

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

            $validatedData = $request->validated();


            $patient = $this->patientService->registerPatient(
                $validatedData,
                $request->file('images')
            );

            $patient->load(['medicalHistory', 'media']);
            return response_success(new PatientResource($patient), 201, 'Created patient successfully.');
        } catch (\Exception $e) {
            return response_error(
                null,
                500,
                'Something went wrong: ' . $e->getMessage()
            );
        }
    }

    public function search(Request $request)
    {
        $term = $request->query('q');
        if (!$term) {
            return response_error(null, 400, 'Search term is required');
        }

        $patient = $this->patientService->searchPatients($term);

        if (!$patient) {
            return response_error(null, 404, 'No patient found.');
        }

        $patient->load(['medicalHistory', 'media']);
        return response_success(new PatientResource($patient), 200, 'Search results.');
    }

    public function show($id)
    {
        try {
            $patient = $this->patientService->getPatientProfile($id);
            return response_success(new PatientResource($patient), 200, 'Patient profile fetched.');
        } catch (ModelNotFoundException $e) {
            return response_error(null, 404, 'Patient not found.');
        }
    }

    public function receptionistWaiting()
    {
        $patients = $this->patientService->getReceptionistWaitingPatients();

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
        ], 200, 'Waiting list fetched successfully.');
    }


    public function update(UpdatePatientRequest $request, $id)
    {
        try {
            $patient = $this->patientService->updatePatient(
                $id,
                $request->validated(),
                $request->file('images')
            );

            return response_success(new PatientResource($patient), 200, 'Updated successfully.');
        } catch (ModelNotFoundException $e) {
            return response_error(null, 404, 'patient id not found');
        } catch (\Exception $e) {
            return response_error(null, 500, 'Update failed: ' . $e->getMessage());
        }
    }


    public function stats()
    {
        try {
            $stats = $this->patientService->getDailyDashboardStats(auth()->id());

            return response_success($stats, 200, 'Daily stats fetched successfully.');
        } catch (\Exception $e) {
            return response_error(null, 500, $e->getMessage());
        }
    }
}
