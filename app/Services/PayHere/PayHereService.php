<?php

namespace App\Services\PayHere;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PayHereService
{
    public function isSandbox(): bool
    {
        return (bool) config('payhere.sandbox');
    }

    public function checkoutUrl(): string
    {
        $key = $this->isSandbox() ? 'sandbox' : 'live';

        return (string) config("payhere.checkout_url.{$key}");
    }

    public function merchantId(): string
    {
        return (string) config('payhere.merchant_id');
    }

    public function currency(): string
    {
        return (string) config('payhere.currency', 'LKR');
    }

    public function notifyUrl(): string
    {
        return (string) config('payhere.notify_url');
    }

    public function frontendUrl(): string
    {
        return (string) config('payhere.frontend_url');
    }

    public function isNotifyUrlLocal(): bool
    {
        $host = parse_url($this->notifyUrl(), PHP_URL_HOST) ?: '';

        return in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with(strtolower($host), '.local');
    }

    public function allowsSandboxReturnConfirm(): bool
    {
        return $this->isSandbox() && (bool) config('payhere.trust_return_in_sandbox');
    }

    public function hasRetrievalCredentials(): bool
    {
        return config('payhere.app_id') !== '' && config('payhere.app_secret') !== '';
    }

    public function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * Checkout request hash (server-side only).
     *
     * hash = UPPER(MD5(merchant_id + order_id + amount + currency + UPPER(MD5(merchant_secret))))
     */
    public function generateCheckoutHash(string $orderId, float $amount, ?string $currency = null): string
    {
        $currency = $currency ?? $this->currency();
        $merchantSecret = (string) config('payhere.merchant_secret');

        return strtoupper(md5(
            $this->merchantId().
            $orderId.
            $this->formatAmount($amount).
            $currency.
            strtoupper(md5($merchantSecret))
        ));
    }

    /**
     * Verify PayHere notify_url md5sig.
     */
    public function verifyNotificationSignature(array $payload): bool
    {
        $merchantId = (string) ($payload['merchant_id'] ?? '');
        $orderId = (string) ($payload['order_id'] ?? '');
        $amount = (string) ($payload['payhere_amount'] ?? '');
        $currency = (string) ($payload['payhere_currency'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $md5sig = strtoupper((string) ($payload['md5sig'] ?? ''));

        if ($merchantId === '' || $orderId === '' || $md5sig === '') {
            return false;
        }

        if ($merchantId !== $this->merchantId()) {
            return false;
        }

        $merchantSecret = (string) config('payhere.merchant_secret');
        $localSig = strtoupper(md5(
            $merchantId.
            $orderId.
            $amount.
            $currency.
            $statusCode.
            strtoupper(md5($merchantSecret))
        ));

        return hash_equals($localSig, $md5sig);
    }

    /**
     * Look up a payment via PayHere Retrieval API (requires App ID / Secret).
     *
     * @return array<string, mixed>|null  First matching RECEIVED payment, or null
     */
    public function retrieveReceivedPayment(string $orderId): ?array
    {
        if (!$this->hasRetrievalCredentials()) {
            return null;
        }

        try {
            $token = $this->oauthAccessToken();
            $key = $this->isSandbox() ? 'sandbox' : 'live';
            $url = (string) config("payhere.retrieval_url.{$key}");

            $response = Http::withToken($token)
                ->acceptJson()
                ->get($url, ['order_id' => $orderId]);

            if (!$response->successful()) {
                Log::warning('PayHere retrieval failed', [
                    'order_id' => $orderId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json('data') ?? [];
            if (!is_array($data)) {
                return null;
            }

            foreach ($data as $row) {
                if (!is_array($row)) {
                    continue;
                }
                if (strtoupper((string) ($row['status'] ?? '')) === 'RECEIVED') {
                    return $row;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('PayHere retrieval exception', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    public function oauthAccessToken(): string
    {
        $cacheKey = 'payhere_oauth_token_'.($this->isSandbox() ? 'sandbox' : 'live');

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            $appId = (string) config('payhere.app_id');
            $appSecret = (string) config('payhere.app_secret');
            if ($appId === '' || $appSecret === '') {
                throw new RuntimeException('PayHere App ID/Secret not configured.');
            }

            $key = $this->isSandbox() ? 'sandbox' : 'live';
            $url = (string) config("payhere.oauth_token_url.{$key}");
            $basic = base64_encode($appId.':'.$appSecret);

            $response = Http::asForm()
                ->withHeaders([
                    'Authorization' => 'Basic '.$basic,
                ])
                ->post($url, [
                    'grant_type' => 'client_credentials',
                ]);

            if (!$response->successful() || !$response->json('access_token')) {
                throw new RuntimeException('Unable to obtain PayHere OAuth token: '.$response->body());
            }

            return (string) $response->json('access_token');
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function buildCheckoutPayload(
        string $orderId,
        float $amount,
        string $items,
        array $customer,
        ?int $bookingId = null
    ): array {
        $amountFormatted = $this->formatAmount($amount);
        $currency = $this->currency();
        $returnUrl = $this->frontendUrl().'/payment/return?order_id='.urlencode($orderId);
        $cancelUrl = $this->frontendUrl().'/payment/cancel?order_id='.urlencode($orderId);

        $nameParts = preg_split('/\s+/', trim((string) ($customer['name'] ?? 'Passenger')), 2) ?: ['Passenger'];
        $firstName = $nameParts[0] !== '' ? $nameParts[0] : 'Passenger';
        $lastName = $nameParts[1] ?? $firstName;

        $payload = [
            'checkout_url' => $this->checkoutUrl(),
            'sandbox' => $this->isSandbox(),
            'merchant_id' => $this->merchantId(),
            'return_url' => $returnUrl,
            'cancel_url' => $cancelUrl,
            'notify_url' => $this->notifyUrl(),
            'order_id' => $orderId,
            'items' => mb_substr($items, 0, 255),
            'currency' => $currency,
            'amount' => $amountFormatted,
            'first_name' => mb_substr($firstName, 0, 40),
            'last_name' => mb_substr($lastName, 0, 40),
            'email' => (string) ($customer['email'] ?? ''),
            'phone' => (string) ($customer['phone'] ?? '0700000000'),
            'address' => (string) ($customer['address'] ?? 'Sri Lanka'),
            'city' => (string) ($customer['city'] ?? 'Colombo'),
            'country' => (string) ($customer['country'] ?? 'Sri Lanka'),
            'hash' => $this->generateCheckoutHash($orderId, $amount, $currency),
            'custom_1' => $bookingId !== null ? (string) $bookingId : '',
            'custom_2' => 'bookkara',
            'notify_url_is_local' => $this->isNotifyUrlLocal(),
            'trust_return_in_sandbox' => $this->allowsSandboxReturnConfirm(),
        ];

        if ($this->isNotifyUrlLocal()) {
            Log::warning('PayHere notify_url is localhost — IPN will not arrive. Enable PAYHERE_TRUST_RETURN_IN_SANDBOX or use a public tunnel.', [
                'notify_url' => $this->notifyUrl(),
            ]);
        }

        return $payload;
    }
}
