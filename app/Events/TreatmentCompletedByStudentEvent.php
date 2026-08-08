<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Treatment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// يُطلق هذا الحدث عند إنهاء الطالب لتنفيذ العلاج وإرساله بانتظار مراجعة المدرّس
class TreatmentCompletedByStudentEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Treatment $treatment) {}
}
