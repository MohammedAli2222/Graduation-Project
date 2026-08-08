<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class DeleteUnverifiedUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected int $userId) {}

    public function handle(): void
    {
        $user = User::find($this->userId);

        if ($user && is_null($user->email_verified_at)) {
            DB::transaction(function () use ($user) {
                $user->tokens()->delete();

                $user->studentProfile()?->delete();
                $user->instructorProfile()?->delete();
                $user->departmentHeadProfile()?->delete();
                $user->storeProfile()?->delete();

                $user->delete();
            });
        }
    }
}
