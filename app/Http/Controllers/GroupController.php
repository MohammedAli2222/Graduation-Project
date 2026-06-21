<?php

namespace App\Http\Controllers;

use App\Repositories\GroupRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    protected $groupRepo;

    public function __construct(GroupRepository $groupRepo)
    {
        $this->groupRepo = $groupRepo;
    }

    public function index(): JsonResponse
    {
        $groups = $this->groupRepo->getAllCached();

        return response_success($groups, 200, 'Groups retrieved successfully.');
    }

    public function getGroupsByYear(Request $request)
    {
        $request->validate([
            'academic_year' => 'required|in:4,5',
        ]);

        try {
            $year = (int) $request->academic_year;
            $groups = $this->groupRepo->getGroupsByYearCached($year);

            return response_success($groups, 200, 'Groups retrieved successfully.');
        } catch (\Exception $e) {
            return response_error(null, 500, $e->getMessage());
        }
    }
}
