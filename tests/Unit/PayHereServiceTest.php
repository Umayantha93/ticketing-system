<?php

namespace Tests\Unit;

use App\Services\PayHere\PayHereService;
use Tests\TestCase;

class PayHereServiceTest extends TestCase
{
    public function test_checkout_hash_matches_payhere_formula(): void
    {
        config([
            'payhere.merchant_id' => '1237223',
            'payhere.merchant_secret' => 'test_secret',
            'payhere.currency' => 'LKR',
        ]);

        $service = new PayHereService();
        $orderId = 'PAY-ABC12345XY';
        $amount = 1260.5;

        $expected = strtoupper(md5(
            '1237223'.
            $orderId.
            '1260.50'.
            'LKR'.
            strtoupper(md5('test_secret'))
        ));

        $this->assertSame($expected, $service->generateCheckoutHash($orderId, $amount));
    }

    public function test_notification_signature_verification(): void
    {
        config([
            'payhere.merchant_id' => '1237223',
            'payhere.merchant_secret' => 'test_secret',
        ]);

        $service = new PayHereService();
        $payload = [
            'merchant_id' => '1237223',
            'order_id' => 'PAY-ABC12345XY',
            'payhere_amount' => '1000.00',
            'payhere_currency' => 'LKR',
            'status_code' => '2',
        ];

        $payload['md5sig'] = strtoupper(md5(
            $payload['merchant_id'].
            $payload['order_id'].
            $payload['payhere_amount'].
            $payload['payhere_currency'].
            $payload['status_code'].
            strtoupper(md5('test_secret'))
        ));

        $this->assertTrue($service->verifyNotificationSignature($payload));

        $payload['md5sig'] = 'DEADBEEF';
        $this->assertFalse($service->verifyNotificationSignature($payload));
    }
}
