<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\DepartmentRepository;
use Illuminate\Http\JsonResponse;

class DepartmentController extends Controller
{
    public function __construct(protected DepartmentRepository $departmentRepo) {}

    public function index(): JsonResponse
    {
        $departments = $this->departmentRepo->getAllCached();

        return response_success($departments, 200, 'Departments retrieved successfully.');
    }
}
