<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BusController;


// Public Endpoints
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/trips', [TripController::class, 'index']);
Route::get('/trips/{id}', [TripController::class, 'show']);

// Authenticated Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Passenger only endpoints
    Route::middleware('role:passenger')->group(function () {
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::get('/my-bookings', [BookingController::class, 'passengerBookings']);
    });

    // Bus Owner only endpoints
    Route::middleware('role:owner')->group(function () {
        Route::post('/buses', [BusController::class, 'store']);
        Route::post('/schedules', [TripController::class, 'createSchedule']);
    });
});
