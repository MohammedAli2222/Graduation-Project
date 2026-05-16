<?php
namespace App\Repositories;

use App\Models\PatientDiagnose;

class DiagnosisRepository
{
    public function create(array $data)
    {
        return PatientDiagnose::create($data);
    }
}
