<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        protected UserRepository $userRepo,
        protected OtpService $otpService
    ) {}

    public function verifyUserOtp(int $userId, string $code)
    {

        $user = $this->userRepo->find($userId);

        $this->otpService->verifyOtp($userId, $code);

        $user->update([
            'email_verified_at' => now(),
        ]);
        $user->load($this->getRelationName($user->roles->first()->name));

        $user->tokens()->delete();
        $finalToken = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $finalToken,
        ];
    }

    public function register(array $data)
    {
        return DB::transaction(function () use ($data) {

            $user = $this->userRepo->createUser($data);

            $user->assignRole($data['role']);

            $this->createProfileByRole($user, $data);

            $this->otpService->generateAndSendOtp($user);

            $token = $user->createToken('verification_token', ['verify-otp'])->plainTextToken;

            return [
                'user' => $user->load($this->getRelationName($data['role'])),
                'token' => $token,
            ];
        });
    }

    public function login(array $credentials)
    {
        $user = $this->userRepo->findByEmail($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email or password' => [trans('auth.failed')],
            ]);
        }

        if (is_null($user->email_verified_at)) {

            $this->otpService->generateAndSendOtp($user);

            $tempToken = $user->createToken('verification_token', ['verify-otp'])->plainTextToken;

            throw new \Exception(json_encode([
                'message' => 'Your account is not verified yet. A new OTP code has been sent to your email.',
                'token' => $tempToken,
            ]), 403);
        }

        $role = $user->getRoleNames()->first();
        $relation = $user->getProfileRelationName($role); 

        if ($relation) {
            $user->load($relation);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => new UserResource($user),
            'token' => $token,
        ];
    }

    public function logout($user)
    {
        return $user->currentAccessToken()->delete();
    }

    public function updateProfile($user, array $data)
    {
        $user->loadMissing('studentProfile');

        DB::transaction(function () use ($user, $data) {

            $userData = array_intersect_key($data, array_flip(['first_name', 'last_name', 'email']));

            if (! empty($userData)) {
                $user->update($userData);
            }

            $profileData = array_intersect_key($data, array_flip(['phone']));

            if (! empty($profileData) && $user->studentProfile) {
                $user->studentProfile->update($profileData);
            }
        });

        return $user->refresh()->load('studentProfile');
    }

    protected function createProfileByRole($user, $data)
    {
        switch ($data['role']) {
            case 'student':
                $user->studentProfile()->create($data);
                break;

            case 'instructor':
                $instructor = $user->instructorProfile()->create([
                    'phone' => $data['phone'],
                    'specialty' => $data['specialty'],
                    'specialty_year' => $data['specialty_year'],
                ]);
                if (isset($data['group_ids'])) {
                    $instructor->groups()->attach($data['group_ids']);
                }
                break;

            case 'department_head':
                $user->departmentHeadProfile()->create([
                    'department_id' => $data['department_id'],
                ]);
                break;

            case 'store_owner':
                $user->storeProfile()->create([
                    'store_name' => $data['store_name'],
                    'store_phone' => $data['store_phone'],
                    'store_address' => $data['store_address'],
                ]);
                break;
        }
    }

    protected function getRelationName(string $role)
    {
        return match ($role) {
            'student' => 'studentProfile.group',
            'instructor' => 'instructorProfile',
            'department_head' => 'departmentHeadProfile',
            'store_owner' => 'storeProfile',
            default => null,
        };
    }
}
