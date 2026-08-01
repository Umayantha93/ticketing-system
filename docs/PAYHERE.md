# PayHere payment integration

BookKara uses **PayHere Checkout API** (redirect IPG). The merchant secret never leaves Laravel.

## Flow

```
Passenger selects seats
        │
        ▼
POST /api/bookings  (auth: passenger)
  • lock seats → status reserved
  • create booking + payment (pending)
  • return checkout fields + server hash
        │
        ▼
Browser POST form → PayHere sandbox/live checkout
        │
        ├─ notify_url  POST /api/payments/payhere/notify  (server-to-server)
        │     verify md5sig → mark paid → seats booked → emails
        │
        ├─ return_url  /payment/return?order_id=PAY-…
        │     UI polls GET /api/bookings/by-order/{orderId}
        │
        └─ cancel_url  /payment/cancel?order_id=PAY-…
              POST /api/bookings/cancel-pending → release seats
```

## Environment

```env
PAYHERE_MERCHANT_ID=your_merchant_id
PAYHERE_MERCHANT_SECRET=your_merchant_secret
PAYHERE_SANDBOX=true
PAYHERE_CURRENCY=LKR
PAYHERE_NOTIFY_URL=https://your-public-host/api/payments/payhere/notify
FRONTEND_URL=http://localhost:3000
PAYHERE_RESERVATION_MINUTES=15
```

**Critical:** `PAYHERE_NOTIFY_URL` must be reachable from the internet. Localhost will not receive IPNs — use ngrok / Cloudflare Tunnel and point PayHere domain credentials at that host.

### Local sandbox without a public tunnel

If `PAYHERE_NOTIFY_URL` is localhost, PayHere still charges successfully but never updates your DB. Enable:

```env
PAYHERE_TRUST_RETURN_IN_SANDBOX=true
```

Then the return/poll endpoint finalizes the pending booking after the customer comes back from PayHere. **Never enable this in production.** Prefer a public notify URL or Retrieval API keys (`PAYHERE_APP_ID` / `PAYHERE_APP_SECRET`).

Recover a stuck booking:

```bash
php artisan payhere:confirm TKT-XXXX
# or
php artisan payhere:confirm PAY-XXXX
```


## Sandbox test cards

Successful Visa: `4916217501611292` (see PayHere sandbox docs for CVV/expiry).

## Ops

```bash
php artisan migrate
php artisan bookings:expire-reservations   # or rely on scheduler every minute
php artisan schedule:work                  # local scheduler
```

Pending reservations expire after `PAYHERE_RESERVATION_MINUTES` and seats return to `available`.

## Security checklist

- [ ] Hash generated only on server (`PayHereService`)
- [ ] Notify signature verified before confirming
- [ ] Amount + currency checked against pending payment
- [ ] Idempotent confirm (duplicate notify safe)
- [ ] Merchant secret only in `.env` (never frontend)
