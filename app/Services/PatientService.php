<?php

namespace App\Services;

use App\Enums\PatientStatus;
use App\Repositories\PatientRepository;
use Illuminate\Support\Facades\DB;

class PatientService
{
    protected $repository;
    protected $mediaService;

    public function __construct(PatientRepository $repository, MediaService $mediaService)
    {
        $this->repository = $repository;
        $this->mediaService = $mediaService;
    }


    public function registerPatient(array $data, $images = null)
    {
        return DB::transaction(function () use ($data, $images) {
            $patientData = [
                'full_name' => $data['full_name'],
                'gender'    => $data['gender'],
                'phone'     => $data['phone'],
                'preliminary_diagnosis' => $data['preliminary_diagnosis'] ?? null,
            ];

            $patient = $this->repository->create($patientData);

            $patient->medicalHistory()->create([
                'has_general_diseases'     => $data['has_general_diseases'],
                'general_diseases_details' => $data['general_diseases_details'] ?? null,
                'is_special_needs'         => $data['is_special_needs'],
                'special_needs_details'    => $data['special_needs_details'] ?? null,
                'takes_medications'        => $data['takes_medications'],
                'medications_details'      => $data['medications_details'] ?? null,
                'has_allergies'            => $data['has_allergies'],
                'allergies_details'        => $data['allergies_details'] ?? null,
            ]);

            if ($images) {
                $this->mediaService->upload($patient, $images, 'patient_records');
            }

            return $patient;
        });
    }

    public function searchPatients(string $term)
    {
        return $this->repository->search($term);
    }

    public function getPatientProfile(int $id)
    {
        return $this->repository->findWithMedia($id);
    }

    public function getWaitingList()
    {
        return $this->repository->getPatientsByStatus(PatientStatus::WAITING_DIAGNOSIS->value);
    }

    public function updatePatient(int $id, array $data, $images = null)
    {
        return DB::transaction(function () use ($id, $data, $images) {

            $patient = $this->repository->FindOrFail($id);

            $patient->update(collect($data)->only([
                'full_name',
                'gender',
                'phone',
                'preliminary_diagnosis'
            ])->toArray());

            $this->updateMedicalHistory($patient, $data);

            if ($images) {
                $patient->clearMediaCollection('patient_records');
                $this->mediaService->upload($patient, $images, 'patient_records');
            }

            return $patient->fresh('medicalHistory');
        });
    }


    public function getDailyDashboardStats(int $receptionistId)
    {
        return [

            'total_today'   => $this->repository->getReceptionistStatsByStatus($receptionistId),

            'waiting_today' => $this->repository->getReceptionistStatsByStatus($receptionistId, PatientStatus::WAITING_DIAGNOSIS->value),

        ];
    }

    private function updateMedicalHistory($patient, array $data)
    {
        $map = [
            'has_general_diseases' => 'general_diseases_details',
            'is_special_needs'     => 'special_needs_details',
            'takes_medications'    => 'medications_details',
            'has_allergies'        => 'allergies_details'
        ];

        $medicalData = [];

        foreach ($map as $boolField => $detailField) {
            if (array_key_exists($boolField, $data)) {
                $value = filter_var($data[$boolField], FILTER_VALIDATE_BOOLEAN);
                $medicalData[$boolField] = $value;

                $medicalData[$detailField] = $value ? ($data[$detailField] ?? null) : null;
            } elseif (array_key_exists($detailField, $data)) {
                $medicalData[$detailField] = $data[$detailField];
            }
        }

        if (!empty($medicalData)) {
            $patient->medicalHistory()->updateOrCreate(
                ['patient_id' => $patient->id],
                $medicalData
            );
        }
    }
}
