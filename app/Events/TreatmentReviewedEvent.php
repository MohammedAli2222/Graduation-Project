<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Treatment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// يُطلق هذا الحدث عند موافقة أو رفض المدرّس للعلاج الذي أنهاه الطالب
class TreatmentReviewedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Treatment $treatment,
        public readonly string $status
    ) {}
}
