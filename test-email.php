<?php

use App\Models\Booking;
use App\Mail\BookingConfirmation;
use Illuminate\Support\Facades\Mail;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get the most recent booking
$booking = Booking::with(['trip.schedule.bus', 'passenger', 'seats'])
    ->latest()
    ->first();

if ($booking) {
    echo "Sending email to: {$booking->passenger->email}\n";
    echo "Ticket reference: {$booking->ticket_reference}\n";
    
    Mail::to($booking->passenger->email)->send(new BookingConfirmation($booking));
    
    echo "✓ Email sent successfully!\n";
    echo "\nCheck your Mailtrap inbox at: https://mailtrap.io/\n";
} else {
    echo "No bookings found in database.\n";
}
