<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BusController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\TicketController;


// Public Endpoints
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/google', [AuthController::class, 'googleSignIn']);
Route::get('/trips/locations', [TripController::class, 'locations']);
Route::get('/trips', [TripController::class, 'index']);
Route::get('/trips/{id}', [TripController::class, 'show']);

// Authenticated Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Ticket routes (accessible by authenticated users)
    Route::get('/tickets/{id}', [TicketController::class, 'show']);
    Route::post('/tickets/{id}/resend', [TicketController::class, 'resend']);

    // Passenger only endpoints
    Route::middleware('role:passenger')->group(function () {
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::get('/my-bookings', [BookingController::class, 'passengerBookings']);
    });

    // Bus Owner only endpoints
    Route::middleware('role:bus_owner')->group(function () {
        Route::post('/buses', [BusController::class, 'store']);
        Route::put('/buses/{id}', [BusController::class, 'update']);
        Route::post('/schedules', [TripController::class, 'createSchedule']);
        Route::get('/owner/schedules', [OwnerController::class, 'schedules']);
        Route::get('/owner/stats', [OwnerController::class, 'stats']);
        Route::get('/owner/bookings', [OwnerController::class, 'bookings']);
        Route::get('/owner/buses', [OwnerController::class, 'buses']);
    });

    // Admin only endpoints
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/stats', [AdminController::class, 'stats']);
        Route::get('/bookings', [AdminController::class, 'allBookings']);
        Route::get('/users', [AdminController::class, 'allUsers']);
        Route::get('/buses', [AdminController::class, 'allBuses']);
        Route::get('/buses/pending', [AdminController::class, 'getPendingBuses']);
        Route::post('/buses/{id}/approve', [AdminController::class, 'approveBus']);
        Route::post('/buses/{id}/reject', [AdminController::class, 'rejectBus']);
        Route::get('/bus-owners', [AdminController::class, 'getAllBusOwners']);
        Route::post('/register-bus-owner', [AdminController::class, 'registerBusOwner']);
        Route::post('/register-bus', [AdminController::class, 'registerBus']);
    });
});
