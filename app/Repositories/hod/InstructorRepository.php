<?php

declare(strict_types=1);

namespace App\Repositories\hod;

use App\Models\User;
use App\Repositories\Contracts\InstructorRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class InstructorRepository implements InstructorRepositoryInterface
{
    public function __construct(
        protected User $model
    ) {}


    public function getAllInstructors(): Collection
    {
        return $this->model->newQuery()
            ->role('instructor')
            ->with('instructorProfile')
            ->select(['id', 'first_name', 'last_name', 'email'])
            ->get();
    }


    public function findInstructorById(int $instructorId): ?User
    {
        return $this->model->newQuery()
            ->role('instructor')
            ->find($instructorId);
    }
}
