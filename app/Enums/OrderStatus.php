<?php


namespace App\Enums;

enum OrderStatus: string
{
    case PENDING    = 'pending';
    case PROCESSING = 'processing';
    case READY      = 'ready';
    case COMPLETED  = 'completed';
    case REJECTED   = 'rejected';
}
