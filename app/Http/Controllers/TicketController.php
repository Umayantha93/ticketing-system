<?php

namespace App\Http\Controllers;

use App\Mail\BookingConfirmation;
use App\Models\Booking;
use Illuminate\Support\Facades\Mail;

class TicketController extends Controller
{
    public function show($bookingId)
    {
        $booking = $this->findAccessibleBooking($bookingId);

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
            'onboarding_location' => $booking->onboarding_location ?? 'Not specified',
            'seat_numbers' => $booking->seats->pluck('seat_number')->toArray(),
            'ticket_count' => $booking->ticket_count,
            'total_price' => (float) $booking->total_price,
            'price_per_seat' => (float) ($booking->total_price / $booking->ticket_count),
            'booking_date' => $booking->created_at->toDateString(),
            'status' => $booking->payment_status,
        ]);
    }

    public function view($bookingId)
    {
        $booking = $this->findAccessibleBooking($bookingId);

        return view('emails.booking-confirmation', ['booking' => $booking]);
    }

    public function resend($bookingId)
    {
        $booking = $this->findAccessibleBooking($bookingId);

        Mail::to($booking->passenger->email)->send(new BookingConfirmation($booking));

        return response()->json([
            'message' => 'Ticket has been sent to your email address.',
            'email' => $booking->passenger->email,
        ]);
    }

    private function findAccessibleBooking($bookingId): Booking
    {
        $user = auth()->user();
        $query = Booking::with(['trip.schedule.bus', 'seats', 'passenger'])
            ->where('id', $bookingId);

        if ($user->role === 'passenger') {
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'bus_owner') {
            $query->whereHas('trip.schedule.bus', function ($builder) use ($user) {
                $builder->where('user_id', $user->id);
            });
        } elseif ($user->role !== 'admin') {
            abort(403, 'Unauthorized');
        }

        return $query->firstOrFail();
    }
}
