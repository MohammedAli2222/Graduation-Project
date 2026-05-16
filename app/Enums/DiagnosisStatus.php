<?php

namespace App\Enums;

enum DiagnosisStatus: string
{
    case WAITING_APPROVAL = 'waiting_approval';
    case AVAILABLE = 'available';
    case RESERVED = 'reserved';
    case REJECTED = 'rejected';
    case CONVERTED_TO_TREATMENT = 'converted_to_treatment';

    // public function label(): string
    // {
    //     return match($this) {
    //         self::WAITING_APPROVAL => 'بانتظار الموافقة',
    //         self::AVAILABLE => 'متاحة للحجز',
    //         self::RESERVED => 'محجوزة',
    //         self::REJECTED => 'مرفوضة',
    //         self::CONVERTED_TO_TREATMENT => 'قيد المعالجة',
    //     };
    // }
}
