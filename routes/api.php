<?php

use App\Http\Controllers\ReceptionistController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('receptionist')->group(function () {


    Route::post('/patients/store', [ReceptionistController::class, 'store']);
});
