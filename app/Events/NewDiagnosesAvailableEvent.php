<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\PatientDiagnose;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// يُطلق هذا الحدث عند إضافة المدرّس لحالات تشخيصية جديدة متاحة أمام الطلاب
class NewDiagnosesAvailableEvent
{
    use Dispatchable, SerializesModels;

    /**
     * @param array<int, PatientDiagnose> $diagnoses الحالات التشخيصية المُنشأة حديثاً
     */
    public function __construct(public readonly array $diagnoses) {}
}
