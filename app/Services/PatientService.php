<?php

namespace App\Services;

use App\Repositories\PatientRepository;

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
        $patient = $this->repository->create($data);

        if ($images) {
            $this->mediaService->upload($patient, $images, 'patient_records');
        }

        return $patient;
    }
}
