<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\PatientDiagnose;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// يُطلق هذا الحدث عند موافقة أو رفض المدرّس لتشخيص اقترحه الطالب
class DiagnosisReviewedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PatientDiagnose $diagnosis,
        public readonly string $status
    ) {}
}
