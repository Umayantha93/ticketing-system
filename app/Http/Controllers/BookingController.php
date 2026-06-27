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
            'seat_ids.*' => 'required|exists:seats,id',
        ]);

        $trip = $this->bookingRepo->getTripPriceAndSeats($request->trip_id);
        $totalPrice = $trip->schedule->price * count($request->seat_ids);

        ProcessBooking::dispatch(
            auth()->id(),
            $request->trip_id,
            $request->seat_ids,
            $totalPrice
        );

        return response()->json([
            'message' => 'Booking is being processed. You will receive a confirmation shortly.'
            ], 202);
    }

    public function passengerBookings()
    {
        $bookings = Booking::where('user_id', auth()->id())
                ->with('trip.schedule.bus', 'seats')
                ->get();

        return response()->json($bookings, 200);
    }

}
