<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    protected $userRepo;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }


    public function register(array $data)
    {
        return DB::transaction(function () use ($data) {

            $user = $this->userRepo->createUser($data);

            $user->assignRole($data['role']);

            $this->createProfileByRole($user, $data);

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user'  => $user->load($this->getRelationName($data['role'])),
                'token' => $token,
            ];
        });
    }


    public function login(array $credentials)
    {
        $user = $this->userRepo->findByEmail($credentials['email']);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
               'email or password' => [trans('auth.failed')],
            ]);
        }

        $role = $user->getRoleNames()->first();
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user'  => new UserResource($user),
            'token' => $token,
        ];
    }

    public function logout($user)
    {
        return $user->currentAccessToken()->delete();
    }


    protected function createProfileByRole($user, $data)
    {
        switch ($data['role']) {
            case 'student':
                $user->studentProfile()->create($data);
                break;

            case 'instructor':
                $instructor = $user->instructorProfile()->create([
                    'phone'          => $data['phone'],
                    'specialty'      => $data['specialty'],
                    'specialty_year' => $data['specialty_year'],
                ]);
                if (isset($data['group_ids'])) {
                    $instructor->groups()->attach($data['group_ids']);
                }
                break;

            case 'department_head':
                $user->departmentHeadProfile()->create([
                    'department_id' => $data['department_id']
                ]);
                break;

            case 'store_owner':
                $user->storeProfile()->create([
                    'store_name'    => $data['store_name'],
                    'store_phone'  => $data['store_phone'],
                    'store_address' => $data['store_address'],
                ]);
                break;
        }
    }

    protected function getRelationName(string $role)
    {
        return match ($role) {
            'student' => 'studentProfile.group',
            'instructor'      => 'instructorProfile',
            'department_head' => 'departmentHeadProfile',
            'store_owner'     => 'storeProfile',
            default           => null,
        };
    }
}
