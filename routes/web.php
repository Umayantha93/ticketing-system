<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;

Route::get('/', function () {
    return view('welcome');
});

// Ticket view route (for printing)
Route::middleware('auth:sanctum')->get('/tickets/{bookingId}/view', [TicketController::class, 'view'])->name('ticket.view');
