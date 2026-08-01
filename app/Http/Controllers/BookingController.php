<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\Booking\BookingPaymentService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingPaymentService $bookingPayments
    ) {
    }

    /**
     * Start PayHere checkout: reserve seats + return gateway form fields.
     */
    public function store(Request $request)
    {
        $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'seats' => 'required|array|min:1',
            'seats.*.seat_id' => 'required|integer|exists:trip_seats,id',
            'seats.*.gender' => 'required|in:male,female',
            'onboarding_location' => 'required|string|max:255',
        ]);

        $seatSelections = collect($request->input('seats'))
            ->mapWithKeys(fn ($row) => [(int) $row['seat_id'] => (string) $row['gender']])
            ->all();

        try {
            $result = $this->bookingPayments->initiateCheckout(
                (int) auth()->id(),
                (int) $request->trip_id,
                $seatSelections,
                $request->onboarding_location
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Unable to start checkout. Please try again.'], 500);
        }

        return response()->json([
            'message' => 'Checkout ready. Redirect to PayHere to complete payment.',
            'booking' => [
                'id' => $result['booking']->id,
                'ticket_reference' => $result['booking']->ticket_reference,
                'ticket_count' => $result['booking']->ticket_count,
                'total_price' => (float) $result['booking']->total_price,
                'payment_status' => $result['booking']->payment_status,
                'status' => $result['booking']->status,
                'reservation_expires_at' => optional($result['booking']->reservation_expires_at)?->toIso8601String(),
            ],
            'payment' => [
                'payment_reference' => $result['payment']->payment_reference,
                'status' => $result['payment']->status,
                'gross_amount' => (float) $result['payment']->gross_amount,
            ],
            'payhere' => $result['payhere'],
        ], 201);
    }

    public function passengerBookings()
    {
        $bookings = Booking::where('user_id', auth()->id())
            ->with('trip.schedule.bus', 'seats', 'payment')
            ->latest()
            ->get();

        return response()->json($bookings, 200);
    }

    /**
     * Poll payment status after PayHere return_url redirect.
     * Also attempts sync when IPN never reached localhost.
     */
    public function statusByOrder(string $orderId)
    {
        try {
            $booking = $this->bookingPayments->syncAfterCustomerReturn($orderId, (int) auth()->id());
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        return response()->json([
            'order_id' => $orderId,
            'booking_id' => $booking->id,
            'ticket_reference' => $booking->ticket_reference,
            'status' => $booking->status,
            'payment_status' => $booking->payment_status,
            'total_price' => (float) $booking->total_price,
            'trip' => $booking->trip,
            'seats' => $booking->seats,
            'onboarding_location' => $booking->onboarding_location,
        ]);
    }

    /**
     * Explicit sync endpoint (same logic as status poll).
     */
    public function syncPayHere(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string',
        ]);

        try {
            $booking = $this->bookingPayments->syncAfterCustomerReturn(
                $request->order_id,
                (int) auth()->id()
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Unable to sync payment.'], 404);
        }

        return response()->json([
            'message' => $booking->payment_status === 'paid'
                ? 'Payment confirmed.'
                : 'Payment still pending. Waiting for PayHere notification.',
            'order_id' => $request->order_id,
            'booking_id' => $booking->id,
            'ticket_reference' => $booking->ticket_reference,
            'status' => $booking->status,
            'payment_status' => $booking->payment_status,
        ]);
    }

    /**
     * Cancel a pending PayHere checkout and release reserved seats.
     */
    public function cancelPending(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string',
        ]);

        try {
            $booking = $this->bookingPayments->cancelPendingByOrderId(
                $request->order_id,
                (int) auth()->id()
            );
        } catch (RuntimeException $e) {
            $code = $e->getMessage() === 'Unauthorized.' ? 403 : 409;

            return response()->json(['message' => $e->getMessage()], $code);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Unable to cancel booking.'], 404);
        }

        return response()->json([
            'message' => 'Pending booking cancelled and seats released.',
            'booking_id' => $booking->id,
            'payment_status' => $booking->payment_status,
            'status' => $booking->status,
        ]);
    }
}
