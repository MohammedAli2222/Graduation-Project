<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\UpdateStudentProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Exception;
use Illuminate\Http\Request;



class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        try {
            $result = $this->authService->register($request->validated());

            return response_success([
                'token' => $result['token']
            ], 201, 'Account created successfully! Please check your email for the OTP verification code.');
        } catch (\Exception $e) {
            return response_error(null, 500, $e->getMessage());
        }
    }



    public function login(LoginRequest $request)
    {
        try {

            $result = $this->authService->login($request->validated());



            return response_success($result, 200, 'Logged in successfully');
        } catch (\Exception $e) {

            if ($e->getCode() == 403) {
                $errorData = json_decode($e->getMessage(), true);
                return response_error(
                    ['token' => $errorData['token']],
                    403,
                    $errorData['message']
                );
            }

            $statusCode = in_array($e->getCode(), [401, 422]) ? $e->getCode() : 500;
            return response_error(null, $statusCode, $e->getMessage());
        }
    }



    public function logout(Request $request)
    {
        try {
            $this->authService->logout($request->user());

            return response_success(null, 200, 'Logged out successfully');
        } catch (\Exception $e) {
            return response_error(null, 500, 'Logout failed: ' . $e->getMessage());
        }
    }


    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp_code' => 'required|string|size:6',
        ]);

        try {
            $result = $this->authService->verifyUserOtp(auth()->id(), $request->otp_code);

            $userData = new UserResource($result['user']);


            return response_success([
                'user'  => $userData,
                'token' => $result['token']
            ], 200, 'Your account has been verified and activated successfully.');
        } catch (\Exception $e) {
            $statusCode = $e->getCode() == 422 ? 422 : 500;
            return response_error(null, $statusCode, $e->getMessage());
        }
    }

    public function updateProfile(UpdateStudentProfileRequest $request)
    {
        $validatedData = $request->validated();

        try {
            $updatedUser = $this->authService->updateProfile(auth()->user(), $validatedData);
            return response_success(new UserResource($updatedUser), 200, 'Profile updated successfully.');
        } catch (Exception $e) {
            $statusCode = ($e->getCode() >= 400 && $e->getCode() <= 500) ? $e->getCode() : 400;
            return response_error(null, $statusCode, $e->getMessage());
        }
    }
}
