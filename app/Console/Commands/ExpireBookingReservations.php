<?php

namespace App\Console\Commands;

use App\Services\Booking\BookingPaymentService;
use Illuminate\Console\Command;

class ExpireBookingReservations extends Command
{
    protected $signature = 'bookings:expire-reservations';

    protected $description = 'Release seats for PayHere checkouts that expired without payment';

    public function handle(BookingPaymentService $bookingPayments): int
    {
        $count = $bookingPayments->expireStaleReservations();
        $this->info("Expired {$count} pending reservation(s).");

        return self::SUCCESS;
    }
}
