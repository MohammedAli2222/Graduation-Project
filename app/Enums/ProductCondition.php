<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductCondition: string
{
    case NEW  = 'new';
    case USED = 'used';  
}
