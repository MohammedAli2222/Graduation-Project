<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// يُطلق هذا الحدث عند قيام رئيس القسم بتفويض صلاحية مراجعة الحالات السريرية لأحد المعيدين
class InstructorDelegatedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly User $instructor) {}
}
