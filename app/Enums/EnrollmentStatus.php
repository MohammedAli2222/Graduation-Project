<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case DROPPED = 'dropped';

    // public function label(): string
    // {
    //     return match($this) {
    //     };
    // }
}
