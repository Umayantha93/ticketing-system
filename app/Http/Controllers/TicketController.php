<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingNotificationMail;

class TicketController extends Controller
{
    public function show($bookingId)
    {
        $user = auth()->user();
        $query = Booking::with(['trip.schedule.bus', 'seats', 'passenger'])
            ->where('id', $bookingId);

        // Allow access if user is the passenger OR if user is the bus owner
        if ($user->role === 'passenger') {
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'bus_owner') {
            // Check if the booking is for one of the owner's buses
            $query->whereHas('trip.schedule.bus', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $booking = $query->firstOrFail();

        $ticketData = [
            'booking_reference' => $booking->ticket_reference,
            'passenger_name' => $booking->passenger->name,
            'passenger_email' => $booking->passenger->email,
            'passenger_phone' => $booking->passenger->phone_number,
            'bus_number' => $booking->trip->schedule->bus->bus_number_plate,
            'transport_contact_number' => $booking->trip->schedule->bus->phone_number,
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
            'total_price' => (float) $booking->total_price,
            'price_per_seat' => (float) ($booking->total_price / $booking->ticket_count),
            'booking_date' => $booking->created_at->toDateString(),
            'status' => $booking->payment_status,
        ];

        return response()->json($ticketData);
    }

    public function resend($bookingId)
    {
        $user = auth()->user();
        $query = Booking::with(['trip.schedule.bus.busOwner', 'seats', 'passenger'])
            ->where('id', $bookingId);

        if ($user->role === 'passenger') {
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'bus_owner') {
            $query->whereHas('trip.schedule.bus', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $booking = $query->firstOrFail();

        $bookingDetails = [
            'bus_number_plate' => $booking->trip->schedule->bus->bus_number_plate,
            'transport_contact_number' => $booking->trip->schedule->bus->phone_number,
            'bus_model' => $booking->trip->schedule->bus->model,
            'origin' => $booking->trip->schedule->origin,
            'destination' => $booking->trip->schedule->destination,
            'departure_date' => $booking->trip->departure_date,
            'departure_time' => $booking->trip->schedule->departure_time,
            'onboarding_location' => $booking->onboarding_location ?? 'Not specified',
            'seat_numbers' => $booking->seats->pluck('seat_number')->toArray(),
            'ticket_count' => $booking->ticket_count,
            'ticket_reference' => $booking->ticket_reference,
            'total_price' => (float) $booking->total_price,
            'passenger_name' => $booking->passenger?->name,
        ];

        if ($booking->passenger?->email) {
            Mail::to($booking->passenger->email)->send(new BookingNotificationMail($bookingDetails, 'passenger'));
        }

        $busOwner = $booking->trip->schedule->bus->busOwner;
        if ($busOwner?->email) {
            Mail::to($busOwner->email)->send(new BookingNotificationMail($bookingDetails, 'owner'));
        }

        return response()->json(['message' => 'Ticket emails resent to passenger and bus owner']);
    }
}
