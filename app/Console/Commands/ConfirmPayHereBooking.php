<?php

namespace App\Console\Commands;

use App\Services\Booking\BookingPaymentService;
use Illuminate\Console\Command;

class ConfirmPayHereBooking extends Command
{
    protected $signature = 'payhere:confirm {reference : Ticket reference (TKT-…) or payment order id (PAY-…)}';

    protected $description = 'Force-confirm a pending PayHere booking after a successful gateway payment (IPN recovery)';

    public function handle(BookingPaymentService $bookingPayments): int
    {
        $reference = (string) $this->argument('reference');

        try {
            $booking = $bookingPayments->forceConfirmByTicketOrOrder($reference);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Confirmed booking {$booking->ticket_reference} (payment_status={$booking->payment_status}, status={$booking->status}).");

        return self::SUCCESS;
    }
}
