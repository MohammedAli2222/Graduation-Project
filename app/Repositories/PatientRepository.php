<?php

namespace App\Repositories;

use App\Models\Patient;

class PatientRepository
{
    public function create(array $data)
    {
        return Patient::create($data);
    }
}
