<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;

class TicketController extends Controller
{
    public function show($bookingId)
    {
        $booking = Booking::with(['trip.schedule.bus', 'seats', 'passenger'])
            ->where('id', $bookingId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $ticketData = [
            'booking_reference' => $booking->ticket_reference,
            'passenger_name' => $booking->passenger->name,
            'passenger_email' => $booking->passenger->email,
            'passenger_phone' => $booking->passenger->phone_number,
            'bus_number' => $booking->trip->schedule->bus->bus_number_plate,
            'bus_model' => $booking->trip->schedule->bus->model,
            'layout_type' => $booking->trip->schedule->bus->layout_type,
            'origin' => $booking->trip->schedule->origin,
            'destination' => $booking->trip->schedule->destination,
            'departure_date' => $booking->trip->departure_date,
            'departure_time' => $booking->trip->schedule->departure_time,
            'arrival_time' => $booking->trip->schedule->estimated_arrival_time,
            'onboarding_location' => $booking->onboarding_location ?? 'Not specified',
            'seat_numbers' => $booking->seats->pluck('seat_number')->toArray(),
            'ticket_count' => $booking->ticket_count,
            'total_price' => $booking->total_price,
            'price_per_seat' => $booking->total_price / $booking->ticket_count,
            'booking_date' => $booking->created_at->toDateString(),
            'status' => $booking->payment_status,
        ];

        return response()->json($ticketData);
    }

    public function resend($bookingId)
    {
        // Placeholder for resend functionality
        // In a real application, you would send an email here
        return response()->json(['message' => 'Ticket resent successfully']);
    }
}
