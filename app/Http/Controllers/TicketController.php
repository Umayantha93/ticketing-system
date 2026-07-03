<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Mail\BookingConfirmation;
use Illuminate\Support\Facades\Mail;

class TicketController extends Controller
{
    /**
     * View ticket by booking ID
     */
    public function view($bookingId)
    {
        $booking = Booking::with(['trip.schedule.bus', 'passenger', 'seats'])
            ->findOrFail($bookingId);

        // Verify that the authenticated user owns this booking
        if (auth()->user()->id !== $booking->user_id && auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return view('emails.booking-confirmation', ['booking' => $booking]);
    }

    /**
     * Resend ticket email
     */
    public function resend($bookingId)
    {
        $booking = Booking::with(['trip.schedule.bus', 'passenger', 'seats'])
            ->findOrFail($bookingId);

        // Verify that the authenticated user owns this booking
        if (auth()->user()->id !== $booking->user_id && auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        Mail::to($booking->passenger->email)->send(new BookingConfirmation($booking));

        return response()->json([
            'message' => 'Ticket has been sent to your email address.',
            'email' => $booking->passenger->email
        ]);
    }

    /**
     * Get ticket data for frontend
     */
    public function getTicket($bookingId)
    {
        $booking = Booking::with(['trip.schedule.bus', 'passenger', 'seats'])
            ->findOrFail($bookingId);

        // Verify that the authenticated user owns this booking
        if (auth()->user()->id !== $booking->user_id && auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
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
            'seat_numbers' => $booking->seats->pluck('seat_number'),
            'ticket_count' => $booking->ticket_count,
            'total_price' => $booking->total_price,
            'price_per_seat' => $booking->total_price / $booking->ticket_count,
            'booking_date' => $booking->created_at,
            'status' => $booking->status
        ]);
    }
}
