<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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

            $userData = new UserResource($result['user']);

            return response_success([
                'user'  => $userData,
                'token' => $result['token']
            ], 201, 'Account created successfully');

        } catch (\Exception $e) {
            return response_error(null, 500, $e->getMessage());
        }
    }



    public function login(LoginRequest $request)
    {
        try {

            $result = $this->authService->login($request->validated());

            return response_success($result, 200, 'Logged in successfully');
        } catch (ValidationException $e) {
            return response_error($e->errors(), 422, 'Invalid credentials');
        } catch (\Exception $e) {
            return response_error(null, 500, 'An error occurred during login');
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
}
