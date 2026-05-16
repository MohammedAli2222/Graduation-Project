<?php

use App\Http\Controllers\InstructorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReceptionistController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);



Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);


    Route::middleware(['auth:sanctum', 'permission:view waiting list'])->group(function () {
        Route::get('/waiting-list', [ReceptionistController::class, 'waitingList']);
    });

    Route::middleware('role:receptionist')->prefix('receptionist')->group(function () {


        Route::post('/patients/update/{id}', [ReceptionistController::class, 'update']);


        Route::post('/patients/store', [ReceptionistController::class, 'store']);

        Route::get('/patients/search', [ReceptionistController::class, 'search']);


        Route::get('/patients/{id}', [ReceptionistController::class, 'show']);

        Route::get('/stats', [ReceptionistController::class, 'stats']);
    });
});

Route::middleware(['auth:sanctum', 'role:instructor'])->group(function () {
    Route::post('/instructor/diagnose', [InstructorController::class, 'diagnose']);
});
