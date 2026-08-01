<?php

namespace App\Http\Controllers;

use App\Services\Booking\BookingPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class PayHereController extends Controller
{
    public function __construct(
        private readonly BookingPaymentService $bookingPayments
    ) {
    }

    /**
     * PayHere server-to-server notification (form-urlencoded POST).
     * Must remain CSRF-free and publicly reachable.
     */
    public function notify(Request $request)
    {
        $payload = $request->all();

        Log::info('PayHere notify received', [
            'order_id' => $payload['order_id'] ?? null,
            'status_code' => $payload['status_code'] ?? null,
            'payment_id' => $payload['payment_id'] ?? null,
        ]);

        try {
            $this->bookingPayments->confirmFromPayHereNotification($payload);

            return response('OK', 200);
        } catch (RuntimeException $e) {
            // Invalid signature / business rule — acknowledge to avoid endless retries
            Log::warning('PayHere notify rejected', [
                'message' => $e->getMessage(),
                'order_id' => $payload['order_id'] ?? null,
            ]);

            return response('OK', 200);
        } catch (Throwable $e) {
            // Transient errors — ask PayHere to retry
            Log::error('PayHere notify error', [
                'message' => $e->getMessage(),
                'order_id' => $payload['order_id'] ?? null,
            ]);

            return response('ERROR', 500);
        }
    }
}
