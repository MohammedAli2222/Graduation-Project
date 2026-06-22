<?php

namespace App\Enums;

enum ProductAvailability: string
{
    case AVAILABLE    = 'available';       // متوفر
    case LIMITED      = 'limited';         // كمية محدودة
    case OUT_OF_STOCK = 'out_of_stock';    // غير متوفر
}
