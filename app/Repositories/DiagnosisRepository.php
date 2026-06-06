<?php

namespace App\Repositories;

use App\Enums\DiagnosisStatus;
use App\Models\PatientDiagnose;

class DiagnosisRepository
{

    public function FindOrFail(int $id)
    {
        return PatientDiagnose::findOrFail($id);
    }

    public function create(array $data)
    {
        return PatientDiagnose::create($data);
    }

    public function update(PatientDiagnose $diagnosis, array $data)
    {
        return $diagnosis->update($data);
    }

    public function findAvailableDiagnosis(int $diagnosisId)
    {
        return PatientDiagnose::where('id', $diagnosisId)
            ->where('status', DiagnosisStatus::AVAILABLE->value)
            ->first();
    }


}
