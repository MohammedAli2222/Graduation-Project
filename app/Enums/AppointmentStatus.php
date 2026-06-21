<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case SCHEDULED = 'scheduled';
    case ATTENDED = 'attended';
    case MISSED = 'missed';
    case CANCELLED = 'cancelled';
}
