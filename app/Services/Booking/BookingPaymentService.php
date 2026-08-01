<?php

namespace App\Services\Booking;

use App\Mail\BookingNotificationMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Trip;
use App\Models\TripSeat;
use App\Models\User;
use App\Services\PayHere\PayHereService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class BookingPaymentService
{
    private const SERVICE_CHARGE_RATE = 0.20;
    private const OTHER_CHARGE_RATE = 0.06;
    private const CUSTOMER_CHARGE_MULTIPLIER = 1.26;
    private const MAX_ONLINE_BOOKABLE_SEATS = 10;
    private const TICKET_REFERENCE_PREFIX = 'TKT-';
    private const TICKET_REFERENCE_LENGTH = 8;
    private const PAYMENT_REFERENCE_PREFIX = 'PAY-';
    private const PAYMENT_REFERENCE_LENGTH = 10;

    public function __construct(
        private readonly PayHereService $payHere
    ) {
    }

    /**
     * Reserve seats and return PayHere checkout parameters.
     *
     * @param  list<int|string>  $seatIds
     * @return array{booking: Booking, payment: Payment, payhere: array<string, mixed>}
     */
    /**
     * Reserve seats and return PayHere checkout parameters.
     *
     * @param  array<int, string>  $seatSelections  map of seat_id => male|female
     * @return array{booking: Booking, payment: Payment, payhere: array<string, mixed>}
     */
    public function initiateCheckout(
        int $userId,
        int $tripId,
        array $seatSelections,
        string $onboardingLocation
    ): array {
        if ($this->payHere->merchantId() === '' || (string) config('payhere.merchant_secret') === '') {
            throw new RuntimeException('PayHere is not configured. Set PAYHERE_MERCHANT_ID and PAYHERE_MERCHANT_SECRET.');
        }

        if ($seatSelections === []) {
            throw new InvalidArgumentException('At least one seat is required.');
        }

        foreach ($seatSelections as $seatId => $gender) {
            if (!in_array($gender, ['male', 'female'], true)) {
                throw new InvalidArgumentException("Invalid gender for seat {$seatId}.");
            }
        }

        $seatIds = array_values(array_unique(array_map('intval', array_keys($seatSelections))));

        $result = DB::transaction(function () use ($userId, $tripId, $seatIds, $seatSelections, $onboardingLocation) {
            $trip = Trip::with('schedule.bus')->findOrFail($tripId);
            if (!$trip->schedule) {
                throw new RuntimeException('Trip schedule is missing.');
            }

            // Lock all seats on the trip so capacity checks stay consistent
            TripSeat::where('trip_id', $tripId)->lockForUpdate()->get();

            $takenCount = TripSeat::where('trip_id', $tripId)
                ->whereIn('status', ['reserved', 'booked'])
                ->count();

            if ($takenCount >= self::MAX_ONLINE_BOOKABLE_SEATS) {
                throw new RuntimeException('This trip is fully booked for online reservations.');
            }

            if ($takenCount + count($seatIds) > self::MAX_ONLINE_BOOKABLE_SEATS) {
                $remaining = self::MAX_ONLINE_BOOKABLE_SEATS - $takenCount;
                throw new RuntimeException(
                    "Only {$remaining} online seat(s) left on this trip (max ".self::MAX_ONLINE_BOOKABLE_SEATS.').'
                );
            }

            $seats = TripSeat::whereIn('id', $seatIds)
                ->where('trip_id', $tripId)
                ->lockForUpdate()
                ->get();

            if ($seats->count() !== count($seatIds)) {
                throw new InvalidArgumentException('One or more seats do not belong to this trip.');
            }

            foreach ($seats as $seat) {
                if ($seat->status !== 'available') {
                    throw new RuntimeException("Seat {$seat->seat_number} is no longer available.");
                }
            }

            $seatCount = $seats->count();
            $basePricePerSeat = (float) $trip->schedule->price;
            $pricePerSeat = $basePricePerSeat
                + ($basePricePerSeat * self::SERVICE_CHARGE_RATE)
                + ($basePricePerSeat * self::OTHER_CHARGE_RATE);
            $totalPrice = round($pricePerSeat * $seatCount, 2);

            $booking = Booking::create([
                'user_id' => $userId,
                'trip_id' => $tripId,
                'ticket_reference' => $this->generateTicketReference(),
                'ticket_count' => $seatCount,
                'total_price' => $totalPrice,
                'onboarding_location' => $onboardingLocation,
                'status' => 'pending',
                'payment_method' => 'payhere',
                'payment_status' => 'pending',
                'reservation_expires_at' => now()->addMinutes((int) config('payhere.reservation_minutes', 15)),
            ]);

            $breakdown = $this->buildPaymentBreakdown($totalPrice);

            $payment = Payment::create([
                'booking_id' => $booking->id,
                'user_id' => $userId,
                'trip_id' => $tripId,
                'payment_reference' => $this->generatePaymentReference(),
                'method' => 'payhere',
                'status' => 'pending',
                'gross_amount' => $breakdown['gross_amount'],
                'base_fare_amount' => $breakdown['base_fare_amount'],
                'service_charge_amount' => $breakdown['service_charge_amount'],
                'other_charge_amount' => $breakdown['other_charge_amount'],
                'owner_payout_amount' => $breakdown['owner_payout_amount'],
                'admin_service_amount' => $breakdown['admin_service_amount'],
                'admin_other_amount' => $breakdown['admin_other_amount'],
                'admin_profit_amount' => $breakdown['admin_profit_amount'],
                'paid_at' => null,
                'meta' => [
                    'gateway' => 'payhere',
                    'sandbox' => $this->payHere->isSandbox(),
                    'seat_genders' => $seatSelections,
                ],
            ]);

            foreach ($seats as $seat) {
                $seat->update([
                    'status' => 'reserved',
                    'passenger_gender' => $seatSelections[(int) $seat->id],
                ]);
                $booking->seats()->attach($seat->id);
            }

            return compact('booking', 'payment', 'trip');
        });

        /** @var Booking $booking */
        $booking = $result['booking'];
        /** @var Payment $payment */
        $payment = $result['payment'];
        /** @var Trip $trip */
        $trip = $result['trip'];
        /** @var User $user */
        $user = User::findOrFail($userId);

        $items = sprintf(
            'Bus ticket %s → %s (%d seat%s)',
            $trip->schedule->origin,
            $trip->schedule->destination,
            $booking->ticket_count,
            $booking->ticket_count === 1 ? '' : 's'
        );

        $payhere = $this->payHere->buildCheckoutPayload(
            $payment->payment_reference,
            (float) $booking->total_price,
            $items,
            [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone_number ?: '0700000000',
                'address' => 'Sri Lanka',
                'city' => 'Colombo',
                'country' => 'Sri Lanka',
            ],
            $booking->id
        );

        return [
            'booking' => $booking->fresh(['seats', 'trip.schedule.bus']),
            'payment' => $payment,
            'payhere' => $payhere,
        ];
    }

    /**
     * Finalize booking after a verified PayHere success notification.
     */
    public function confirmFromPayHereNotification(array $payload): Booking
    {
        if (!$this->payHere->verifyNotificationSignature($payload)) {
            throw new RuntimeException('Invalid PayHere notification signature.');
        }

        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (int) ($payload['status_code'] ?? 0);

        return DB::transaction(function () use ($orderId, $statusCode, $payload) {
            $payment = Payment::where('payment_reference', $orderId)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                throw new RuntimeException('Payment not found for order_id.');
            }

            /** @var Booking $booking */
            $booking = Booking::with(['seats', 'trip.schedule.bus.busOwner', 'passenger'])
                ->lockForUpdate()
                ->findOrFail($payment->booking_id);

            // Idempotent success
            if ($payment->status === 'paid' && $booking->payment_status === 'paid') {
                return $booking;
            }

            if ($statusCode === 2) {
                $this->assertAmountMatches($payment, $payload);

                return $this->markBookingPaid($booking, $payment, [
                    'gateway' => 'payhere',
                    'payhere_payment_id' => $payload['payment_id'] ?? null,
                    'payhere_method' => $payload['method'] ?? null,
                    'status_message' => $payload['status_message'] ?? null,
                    'card_holder_name' => $payload['card_holder_name'] ?? null,
                    'card_no' => $payload['card_no'] ?? null,
                    'notified_at' => now()->toIso8601String(),
                    'confirmed_via' => 'notify',
                ], strtolower((string) ($payload['method'] ?? 'payhere')));
            }

            if (in_array($statusCode, [-1, -2, -3], true)) {
                $this->releaseReservation($booking, $payment, $statusCode === -1 ? 'cancelled' : 'failed', $payload);

                return $booking->fresh(['seats', 'trip.schedule.bus', 'passenger']);
            }

            // status_code 0 = pending — leave reservation as-is
            return $booking;
        });
    }

    /**
     * After PayHere return_url: if IPN never arrived, sync via Retrieval API
     * or (sandbox only) trust-return fallback when enabled.
     */
    public function syncAfterCustomerReturn(string $orderId, int $userId): Booking
    {
        $booking = $this->findByOrderIdForUser($orderId, $userId);

        if ($booking->payment_status === 'paid') {
            return $booking;
        }

        if ($booking->payment_status !== 'pending' || $booking->status !== 'pending') {
            return $booking;
        }

        // Prefer verifying with PayHere Retrieval API when credentials exist
        $retrieved = $this->payHere->retrieveReceivedPayment($orderId);
        if ($retrieved !== null) {
            return DB::transaction(function () use ($orderId, $retrieved) {
                $payment = Payment::where('payment_reference', $orderId)->lockForUpdate()->firstOrFail();
                $booking = Booking::with(['seats', 'trip.schedule.bus.busOwner', 'passenger'])
                    ->lockForUpdate()
                    ->findOrFail($payment->booking_id);

                if ($payment->status === 'paid') {
                    return $booking->fresh(['seats', 'trip.schedule.bus', 'payment', 'passenger']);
                }

                $amount = isset($retrieved['amount_detail']['gross'])
                    ? (float) $retrieved['amount_detail']['gross']
                    : (isset($retrieved['amount']) ? (float) $retrieved['amount'] : null);

                if ($amount !== null && abs($amount - (float) $payment->gross_amount) > 0.009) {
                    throw new RuntimeException('Retrieved PayHere amount does not match booking.');
                }

                $method = strtolower((string) data_get($retrieved, 'payment_method.method', 'payhere'));

                return $this->markBookingPaid($booking, $payment, [
                    'gateway' => 'payhere',
                    'payhere_payment_id' => $retrieved['payment_id'] ?? null,
                    'payhere_method' => data_get($retrieved, 'payment_method.method'),
                    'confirmed_via' => 'retrieval_api',
                    'retrieved_at' => now()->toIso8601String(),
                ], $method);
            });
        }

        // Local sandbox: PayHere cannot POST to localhost notify_url
        if ($this->payHere->allowsSandboxReturnConfirm()) {
            return DB::transaction(function () use ($orderId) {
                $payment = Payment::where('payment_reference', $orderId)->lockForUpdate()->firstOrFail();
                $booking = Booking::with(['seats', 'trip.schedule.bus.busOwner', 'passenger'])
                    ->lockForUpdate()
                    ->findOrFail($payment->booking_id);

                if ($payment->status === 'paid') {
                    return $booking->fresh(['seats', 'trip.schedule.bus', 'payment', 'passenger']);
                }

                return $this->markBookingPaid($booking, $payment, [
                    'gateway' => 'payhere',
                    'confirmed_via' => 'sandbox_return_fallback',
                    'note' => 'IPN unreachable (localhost). Confirmed after customer return in sandbox.',
                    'confirmed_at' => now()->toIso8601String(),
                ], 'payhere');
            });
        }

        return $booking;
    }

    /**
     * Manually confirm a pending paid booking (ops / stuck IPN recovery).
     */
    public function forceConfirmByTicketOrOrder(string $reference): Booking
    {
        $payment = Payment::where('payment_reference', $reference)->first();
        $booking = $payment
            ? Booking::with(['seats', 'trip.schedule.bus.busOwner', 'passenger', 'payment'])->find($payment->booking_id)
            : Booking::with(['seats', 'trip.schedule.bus.busOwner', 'passenger', 'payment'])
                ->where('ticket_reference', $reference)
                ->first();

        if (!$booking) {
            throw new RuntimeException('Booking not found for reference: '.$reference);
        }

        if ($booking->payment_status === 'paid') {
            return $booking;
        }

        return DB::transaction(function () use ($booking) {
            $payment = Payment::where('booking_id', $booking->id)->lockForUpdate()->firstOrFail();
            $booking = Booking::with(['seats', 'trip.schedule.bus.busOwner', 'passenger'])
                ->lockForUpdate()
                ->findOrFail($booking->id);

            return $this->markBookingPaid($booking, $payment, [
                'gateway' => 'payhere',
                'confirmed_via' => 'artisan_force_confirm',
                'confirmed_at' => now()->toIso8601String(),
            ], 'payhere');
        });
    }

    private function markBookingPaid(Booking $booking, Payment $payment, array $metaExtra, string $method = 'payhere'): Booking
    {
        foreach ($booking->seats as $seat) {
            $locked = TripSeat::where('id', $seat->id)->lockForUpdate()->first();
            if ($locked) {
                $locked->update(['status' => 'booked']);
            }
        }

        $payment->fill([
            'status' => 'paid',
            'method' => $method !== '' ? $method : 'payhere',
            'paid_at' => now(),
            'meta' => array_merge($payment->meta ?? [], $metaExtra),
        ])->save();

        $booking->fill([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_method' => 'payhere',
        ])->save();

        $booking = $booking->fresh(['seats', 'trip.schedule.bus.busOwner', 'passenger', 'payment']);
        $this->sendBookingEmails($booking);

        return $booking;
    }

    public function cancelPendingByOrderId(string $orderId, int $userId): Booking
    {
        return DB::transaction(function () use ($orderId, $userId) {
            $payment = Payment::where('payment_reference', $orderId)->lockForUpdate()->firstOrFail();
            $booking = Booking::with('seats')->lockForUpdate()->findOrFail($payment->booking_id);

            if ((int) $booking->user_id !== $userId) {
                throw new RuntimeException('Unauthorized.');
            }

            if ($booking->payment_status === 'paid') {
                throw new RuntimeException('Paid bookings cannot be cancelled here.');
            }

            $this->releaseReservation($booking, $payment, 'cancelled');

            return $booking->fresh(['seats', 'trip.schedule.bus']);
        });
    }

    public function findByOrderIdForUser(string $orderId, int $userId): Booking
    {
        $payment = Payment::where('payment_reference', $orderId)->firstOrFail();
        $booking = Booking::with(['seats', 'trip.schedule.bus', 'payment'])
            ->where('id', $payment->booking_id)
            ->where('user_id', $userId)
            ->firstOrFail();

        return $booking;
    }

    /**
     * Release expired pending reservations (scheduler).
     */
    public function expireStaleReservations(): int
    {
        $expired = Booking::query()
            ->where('payment_status', 'pending')
            ->where('status', 'pending')
            ->whereNotNull('reservation_expires_at')
            ->where('reservation_expires_at', '<', now())
            ->pluck('id');

        $count = 0;
        foreach ($expired as $bookingId) {
            DB::transaction(function () use ($bookingId, &$count) {
                $booking = Booking::with('payment', 'seats')->lockForUpdate()->find($bookingId);
                if (!$booking || $booking->payment_status !== 'pending') {
                    return;
                }
                $payment = $booking->payment;
                if (!$payment || $payment->status !== 'pending') {
                    return;
                }
                $this->releaseReservation($booking, $payment, 'cancelled', [
                    'reason' => 'reservation_expired',
                ]);
                $count++;
            });
        }

        return $count;
    }

    private function releaseReservation(Booking $booking, Payment $payment, string $paymentStatus, array $extraMeta = []): void
    {
        foreach ($booking->seats as $seat) {
            $locked = TripSeat::where('id', $seat->id)->lockForUpdate()->first();
            if ($locked && in_array($locked->status, ['reserved', 'available'], true)) {
                // Only free seats still reserved for this pending payment
                if ($locked->status === 'reserved') {
                    $locked->update([
                        'status' => 'available',
                        'passenger_gender' => null,
                    ]);
                }
            }
        }

        $payment->fill([
            'status' => $paymentStatus === 'cancelled' ? 'failed' : $paymentStatus,
            'meta' => array_merge($payment->meta ?? [], $extraMeta, [
                'released_at' => now()->toIso8601String(),
            ]),
        ])->save();

        $booking->fill([
            'status' => 'cancelled',
            'payment_status' => $paymentStatus === 'cancelled' ? 'failed' : $paymentStatus,
        ])->save();
    }

    private function assertAmountMatches(Payment $payment, array $payload): void
    {
        $paidAmount = isset($payload['payhere_amount'])
            ? round((float) $payload['payhere_amount'], 2)
            : null;
        $expected = round((float) $payment->gross_amount, 2);

        if ($paidAmount === null || abs($paidAmount - $expected) > 0.009) {
            throw new RuntimeException('PayHere amount does not match booking total.');
        }

        $currency = (string) ($payload['payhere_currency'] ?? '');
        if ($currency !== '' && strtoupper($currency) !== strtoupper($this->payHere->currency())) {
            throw new RuntimeException('PayHere currency mismatch.');
        }
    }

    private function sendBookingEmails(Booking $booking): void
    {
        $trip = $booking->trip;
        $passenger = $booking->passenger;

        if (!$trip || !$trip->schedule || !$trip->schedule->bus) {
            return;
        }

        $seatNumbers = $booking->seats->pluck('seat_number')->toArray();

        $bookingDetails = [
            'bus_number_plate' => $trip->schedule->bus->bus_number_plate,
            'transport_contact_number' => $trip->schedule->bus->phone_number,
            'origin' => $trip->schedule->origin,
            'destination' => $trip->schedule->destination,
            'departure_date' => $trip->departure_date,
            'departure_time' => $trip->schedule->departure_time,
            'seat_numbers' => $seatNumbers,
            'ticket_count' => $booking->ticket_count,
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
            $reference = self::TICKET_REFERENCE_PREFIX.strtoupper(Str::random(self::TICKET_REFERENCE_LENGTH));
        } while (Booking::where('ticket_reference', $reference)->exists());

        return $reference;
    }

    private function generatePaymentReference(): string
    {
        do {
            $reference = self::PAYMENT_REFERENCE_PREFIX.strtoupper(Str::random(self::PAYMENT_REFERENCE_LENGTH));
        } while (Payment::where('payment_reference', $reference)->exists());

        return $reference;
    }

    /**
     * @return array{
     *   gross_amount: float,
     *   base_fare_amount: float,
     *   service_charge_amount: float,
     *   other_charge_amount: float,
     *   owner_payout_amount: float,
     *   admin_service_amount: float,
     *   admin_other_amount: float,
     *   admin_profit_amount: float
     * }
     */
    private function buildPaymentBreakdown(float $grossAmount): array
    {
        $baseFare = round($grossAmount / self::CUSTOMER_CHARGE_MULTIPLIER, 2);
        $serviceCharge = round($baseFare * self::SERVICE_CHARGE_RATE, 2);
        $otherCharge = round($baseFare * self::OTHER_CHARGE_RATE, 2);

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
