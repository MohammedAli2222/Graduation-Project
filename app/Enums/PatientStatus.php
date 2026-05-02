<?php

namespace App\Enums;

enum PatientStatus: string
{
    case WAITING_DIAGNOSIS = 'waiting_diagnosis';
    case AVAILABLE = 'available';
    case FULLY_RESERVED = 'fully_reserved';
    case COMPLETED = 'completed';
}
