<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\Booking;
use App\Models\Trip;
use Illuminate\Support\Str;
use App\Models\TripSeat;
use App\Mail\BookingConfirmation;
use App\Mail\BookingNotificationMail;
use Carbon\Carbon;

class ProcessBooking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $tripId;
    protected $seatIds;
    protected $totalPrice;
    protected $onboardingLocation;

    public function __construct($userId, $tripId, $seatIds, $totalPrice, $onboardingLocation = null)
    {
        $this->userId = $userId;
        $this->tripId = $tripId;
        $this->seatIds = $seatIds; // array of tripseat IDs
        $this->totalPrice = $totalPrice;
        $this->onboardingLocation = $onboardingLocation;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $booking = null;

        DB::transaction(function () use (&$booking) {

            $seats = TripSeat::whereIn('id', $this->seatIds)
                    ->where('trip_id', $this->tripId)
                    ->lockForUpdate()
                    ->get();

            foreach ($seats as $seat) {
                if ($seat->status !== 'available') {
                    throw new \Exception("Seat {$seat->seat_number} is already booked.");
                }
            }

            $booking = Booking::create([
                'user_id' => $this->userId,
                'trip_id' => $this->tripId,
                'ticket_reference' => 'TKT-' . strtoupper(Str::random(8)),
                'ticket_count' => count($this->seatIds),
                'total_price' => $this->totalPrice,
                'status' => 'confirmed',
                'onboarding_location' => $this->onboardingLocation,
                'payment_status' => 'paid',
            ]);

            foreach ($seats as $seat) {
                $seat->update(['status' => 'booked']);
                $booking->seats()->attach($seat->id);
            }

            // Check if trip is within 24 hours and send email to bus owner
            $trip = Trip::with('schedule.bus.busOwner')->find($this->tripId);

            if ($trip && $trip->schedule && $trip->schedule->bus) {
                $departureDateTime = Carbon::parse($trip->departure_date . ' ' . $trip->schedule->departure_time);
                $now = Carbon::now();
                $hoursUntilDeparture = $now->diffInHours($departureDateTime, false);

                // If trip is within 24 hours (and in the future)
                if ($hoursUntilDeparture <= 24 && $hoursUntilDeparture > 0) {
                    $busOwner = $trip->schedule->bus->busOwner;

                    if ($busOwner && $busOwner->email) {
                        $seatNumbers = $seats->pluck('seat_number')->toArray();

                        $bookingDetails = [
                            'bus_number_plate' => $trip->schedule->bus->bus_number_plate,
                            'bus_model' => $trip->schedule->bus->model,
                            'origin' => $trip->schedule->origin,
                            'destination' => $trip->schedule->destination,
                            'departure_date' => $trip->departure_date,
                            'departure_time' => $trip->schedule->departure_time,
                            'onboarding_location' => $this->onboardingLocation ?? 'Not specified',
                            'seat_numbers' => $seatNumbers,
                            'ticket_count' => count($this->seatIds),
                            'ticket_reference' => $booking->ticket_reference,
                        ];

                        Mail::to($busOwner->email)->send(new BookingNotificationMail($bookingDetails));
                    }
                }
            }
        });

        // Send email confirmation with ticket
        if ($booking) {
            $booking->load(['trip.schedule.bus', 'passenger', 'seats']);
            Mail::to($booking->passenger->email)->send(new BookingConfirmation($booking));
        }
    }
}
