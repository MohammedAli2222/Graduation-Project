<?php

namespace App\Enums;

enum TreatmentStatus: string
{
    case IN_PROGRESS = 'in_progress';
    case CANCELLED   = 'cancelled';
    case COMPLETED   = 'completed';
    case REJECTED    = 'rejected';
}
