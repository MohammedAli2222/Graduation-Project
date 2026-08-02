<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanUnverifiedUsersCommand extends Command
{
    protected $signature = 'auth:clean-unverified-users';
    protected $description = 'Delete unverified users and their tokens if registered more than 1 hour ago';

    public function handle(): int
    {
        $cutoffTime = now()->subHour();

        $unverifiedUsers = User::whereNull('email_verified_at')
            ->where('created_at', '<=', $cutoffTime)
            ->get();

        $count = $unverifiedUsers->count();

        foreach ($unverifiedUsers as $user) {
            DB::transaction(function () use ($user) {
                $user->tokens()->delete();
                $user->studentProfile()?->delete();
                $user->instructorProfile()?->delete();
                $user->departmentHeadProfile()?->delete();
                $user->storeProfile()?->delete();
                $user->delete();
            });
        }

        $this->info("Cleaned {$count} unverified users successfully.");

        return Command::SUCCESS;
    }
}
