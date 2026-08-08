<?php

namespace App\Enums;

enum DiagnosisStatus: string
{
    case WAITING_APPROVAL = 'waiting_approval';
    case AVAILABLE = 'available';
    case RESERVED = 'reserved';
    case REJECTED = 'rejected';
    case CONVERTED_TO_TREATMENT = 'converted_to_treatment';
    case FINISHED = 'finished';

    // public function label(): string
    // {
    //     return match($this) {
    //     };
    // }
}
