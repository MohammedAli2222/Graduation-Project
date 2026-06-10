<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface InstructorRepositoryInterface
{

    public function getAllInstructors(): Collection;

    public function findInstructorById(int $instructorId): ?User;
}
