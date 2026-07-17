<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Jobs\ProcessBooking;
use App\Models\Booking;

class BookingController extends Controller
{
    private const SERVICE_CHARGE_RATE = 0.20;
    private const OTHER_CHARGE_RATE = 0.06;

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
        $seatCount = count($request->seat_ids);
        $basePricePerSeat = (float) $trip->schedule->price;
        $serviceChargePerSeat = $basePricePerSeat * self::SERVICE_CHARGE_RATE;
        $otherChargePerSeat = $basePricePerSeat * self::OTHER_CHARGE_RATE;
        $pricePerSeat = $basePricePerSeat + $serviceChargePerSeat + $otherChargePerSeat;
        $totalPrice = round($pricePerSeat * $seatCount, 2);

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
