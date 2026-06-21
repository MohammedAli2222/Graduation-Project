<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientMedicalHistory extends Model
{
    protected $fillable = [
        'patient_id',
        'has_general_diseases',
        'general_diseases_details',
        'is_special_needs',
        'special_needs_details',
        'takes_medications',
        'medications_details',
        'has_allergies',
        'allergies_details',
    ];

    protected $casts = [
        'has_general_diseases' => 'boolean',
        'is_special_needs' => 'boolean',
        'takes_medications' => 'boolean',
        'has_allergies' => 'boolean',
    ];
}
