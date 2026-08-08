<?php

namespace App\Enums;

enum ProductAvailability: string
{
    case AVAILABLE    = 'available';
    case LIMITED      = 'limited';
    case OUT_OF_STOCK = 'out_of_stock';
}
