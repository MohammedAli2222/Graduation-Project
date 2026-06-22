<?php


namespace App\Enums;

enum OrderStatus: string
{
    case PENDING    = 'pending';    // الطلب جديد وبانتظار موافقة المتجر
    case PROCESSING = 'processing'; // قيد التجهيز
    case READY      = 'ready';      // جاهز للاستلام
    case COMPLETED  = 'completed';  // تم تسليم الأدوات للطالب
    case REJECTED   = 'rejected';   // الطلب مرفوض 
}
