<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;
use Illuminate\Support\Str;
use App\Models\TripSeat;

class ProcessBooking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $tripId;
    protected $seatIds;
    protected $totalPrice;
    public function __construct($userId, $tripId, $seatIds, $totalPrice)
    {
        $this->userId = $userId;
        $this->tripId = $tripId;
        $this->seatIds = $seatIds; // array of tripseat IDs
        $this->totalPrice = $totalPrice;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {

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
                'ticket_reference' => uniqid('ticket_' . strtoupper(Str::random(8))),
                'ticket_count' => count($this->seatIds),
                'total_price' => $this->totalPrice,
                'payment_status' => 'paid',
            ]);

            foreach ($seats as $seat) {
                $seat->update(['status' => 'booked']);
                $booking->seats()->attach($seat->id);
            }
        });
    }
}
