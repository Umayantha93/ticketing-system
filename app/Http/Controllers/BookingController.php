<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Jobs\ProcessBooking;
use App\Models\Booking;

class BookingController extends Controller
{
    protected $bookingRepo;

    public function __construct(BookingRepositoryInterface $bookingRepo)
    {
        $this->bookingRepo = $bookingRepo;
    }

    public function store(Request $request)
    {
        $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'seat_ids' => 'required|array',
            'seat_ids.*' => 'required|exists:trip_seats,id',
            'onboarding_location' => 'required|string|max:255',
        ]);

        $trip = $this->bookingRepo->getTripPriceAndSeats($request->trip_id);
        $totalPrice = $trip->schedule->price * count($request->seat_ids);

        ProcessBooking::dispatchSync(
            auth()->id(),
            $request->trip_id,
            $request->seat_ids,
            $totalPrice,
            $request->onboarding_location
        );

        return response()->json([
            'message' => 'Booking completed and ticket notifications sent.'
            ], 201);
    }

    public function passengerBookings()
    {
        $bookings = Booking::where('user_id', auth()->id())
                ->with('trip.schedule.bus', 'seats')
                ->get();

        return response()->json($bookings, 200);
    }

}
