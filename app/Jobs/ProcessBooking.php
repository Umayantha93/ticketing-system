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
use App\Models\Payment;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Str;
use App\Models\TripSeat;
use App\Mail\BookingNotificationMail;

class ProcessBooking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $tripId;
    protected $seatIds;
    protected $totalPrice;
    protected $onboardingLocation;

    private const TICKET_REFERENCE_PREFIX = 'TKT-';
    private const TICKET_REFERENCE_LENGTH = 8;
    private const PAYMENT_REFERENCE_PREFIX = 'PAY-';
    private const PAYMENT_REFERENCE_LENGTH = 10;
    private const CUSTOMER_CHARGE_MULTIPLIER = 1.26;

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
        $mailPayload = DB::transaction(function () {

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
                'ticket_reference' => $this->generateTicketReference(),
                'ticket_count' => count($this->seatIds),
                'total_price' => $this->totalPrice,
                'onboarding_location' => $this->onboardingLocation,
                'payment_status' => 'paid',
            ]);

            $paymentBreakdown = $this->buildPaymentBreakdown((float) $booking->total_price);

            Payment::create([
                'booking_id' => $booking->id,
                'user_id' => $this->userId,
                'trip_id' => $this->tripId,
                'payment_reference' => $this->generatePaymentReference(),
                'method' => 'card',
                'status' => 'paid',
                'gross_amount' => $paymentBreakdown['gross_amount'],
                'base_fare_amount' => $paymentBreakdown['base_fare_amount'],
                'service_charge_amount' => $paymentBreakdown['service_charge_amount'],
                'other_charge_amount' => $paymentBreakdown['other_charge_amount'],
                'owner_payout_amount' => $paymentBreakdown['owner_payout_amount'],
                'admin_service_amount' => $paymentBreakdown['admin_service_amount'],
                'admin_other_amount' => $paymentBreakdown['admin_other_amount'],
                'admin_profit_amount' => $paymentBreakdown['admin_profit_amount'],
                'paid_at' => now(),
            ]);

            foreach ($seats as $seat) {
                $seat->update(['status' => 'booked']);
                $booking->seats()->attach($seat->id);
            }

            $trip = Trip::with('schedule.bus.busOwner')->find($this->tripId);
            $passenger = User::find($this->userId);
            $seatNumbers = $seats->pluck('seat_number')->toArray();

            return [
                'trip' => $trip,
                'passenger' => $passenger,
                'seat_numbers' => $seatNumbers,
                'booking' => $booking,
            ];
        });

        $trip = $mailPayload['trip'] ?? null;
        $booking = $mailPayload['booking'] ?? null;
        $seatNumbers = $mailPayload['seat_numbers'] ?? [];
        $passenger = $mailPayload['passenger'] ?? null;

        if (!$trip || !$trip->schedule || !$trip->schedule->bus || !$booking) {
            return;
        }

        $bookingDetails = [
            'bus_number_plate' => $trip->schedule->bus->bus_number_plate,
            'transport_contact_number' => $trip->schedule->bus->phone_number,
            'origin' => $trip->schedule->origin,
            'destination' => $trip->schedule->destination,
            'departure_date' => $trip->departure_date,
            'departure_time' => $trip->schedule->departure_time,
            'seat_numbers' => $seatNumbers,
            'ticket_count' => count($this->seatIds),
            'ticket_reference' => $booking->ticket_reference,
            'total_price' => (float) $booking->total_price,
            'passenger_name' => $passenger?->name,
        ];

        if ($passenger && $passenger->email) {
            Mail::to($passenger->email)->send(new BookingNotificationMail($bookingDetails, 'passenger'));
        }

        $busOwner = $trip->schedule->bus->busOwner;
        if ($busOwner && $busOwner->email) {
            Mail::to($busOwner->email)->send(new BookingNotificationMail($bookingDetails, 'owner'));
        }
    }

    private function generateTicketReference(): string
    {
        do {
            $reference = self::TICKET_REFERENCE_PREFIX . strtoupper(Str::random(self::TICKET_REFERENCE_LENGTH));
        } while (Booking::where('ticket_reference', $reference)->exists());

        return $reference;
    }

    private function generatePaymentReference(): string
    {
        do {
            $reference = self::PAYMENT_REFERENCE_PREFIX . strtoupper(Str::random(self::PAYMENT_REFERENCE_LENGTH));
        } while (Payment::where('payment_reference', $reference)->exists());

        return $reference;
    }

    private function buildPaymentBreakdown(float $grossAmount): array
    {
        $baseFare = round($grossAmount / self::CUSTOMER_CHARGE_MULTIPLIER, 2);
        $serviceCharge = round($baseFare * 0.20, 2);
        $otherCharge = round($baseFare * 0.06, 2);

        $ownerPayout = round($baseFare + ($serviceCharge / 2), 2);
        $adminService = round($serviceCharge / 2, 2);
        $adminOther = round($otherCharge, 2);
        $adminProfit = round($adminService + $adminOther, 2);

        $roundingDifference = round($grossAmount - ($ownerPayout + $adminProfit), 2);
        if ($roundingDifference !== 0.0) {
            $adminOther = round($adminOther + $roundingDifference, 2);
            $adminProfit = round($adminService + $adminOther, 2);
        }

        return [
            'gross_amount' => round($grossAmount, 2),
            'base_fare_amount' => $baseFare,
            'service_charge_amount' => $serviceCharge,
            'other_charge_amount' => $otherCharge,
            'owner_payout_amount' => $ownerPayout,
            'admin_service_amount' => $adminService,
            'admin_other_amount' => $adminOther,
            'admin_profit_amount' => $adminProfit,
        ];
    }
}
