# API reference — BookKara

Base path: `/api`  
Auth header: `Authorization: Bearer {token}`

Success bodies are ad-hoc JSON (often `{ message, ... }` or raw Eloquent). Validation errors use Laravel’s 422 format. Role failures return **403**.

---

## Public

| Method | Path | Description |
|--------|------|-------------|
| POST | `/register` | Create passenger account |
| POST | `/login` | Email/password → token + user |
| POST | `/auth/google` | Google `id_token` → token + user |
| GET | `/trips/locations` | Destination list (multilingual) |
| GET | `/trips` | Search by `origin`, `destination`, `date` |
| GET | `/trips/{id}` | Trip detail + seats |
| POST | `/payments/payhere/notify` | PayHere IPN (form-urlencoded; verify `md5sig`) |

---

## Authenticated (any role)

| Method | Path | Description |
|--------|------|-------------|
| POST | `/logout` | Revoke current token |
| GET | `/tickets/{id}` | Ticket for **booking id** |
| POST | `/tickets/{id}/resend` | Resend ticket email |

Ticket access: owning passenger or bus owner of the trip’s bus (query filtered for those roles).

---

## Passenger (`role:passenger`)

| Method | Path | Description |
|--------|------|-------------|
| POST | `/bookings` | Reserve seats + return PayHere checkout payload |
| GET | `/my-bookings` | Current user’s bookings |
| GET | `/bookings/by-order/{orderId}` | Poll status after PayHere return (`orderId` = payment_reference) |
| POST | `/bookings/cancel-pending` | Body `{ order_id }` — cancel unpaid reservation |

See [PAYHERE.md](./PAYHERE.md) for the checkout contract.

---

## Bus owner (`role:bus_owner`)

| Method | Path | Description |
|--------|------|-------------|
| POST | `/buses` | Register bus (pending approval / inactive) |
| PUT | `/buses/{id}` | Update owned bus |
| POST | `/schedules` | Create weekly schedule |
| GET | `/owner/schedules` | List schedules (`?bus_id=`) |
| GET | `/owner/stats` | Revenue / occupancy style stats |
| GET | `/owner/bookings` | Bookings (`?bus_id=`) |
| GET | `/owner/buses` | Owner’s fleet |

---

## Admin (`role:admin`, prefix `/admin`)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/admin/stats` | Platform stats |
| GET | `/admin/ledger` | Payment ledger (`period`, dates, etc.) |
| GET | `/admin/bookings` | All bookings |
| GET | `/admin/users` | All users |
| GET | `/admin/buses` | All buses |
| GET | `/admin/buses/pending` | Pending approval |
| POST | `/admin/buses/{id}/approve` | Approve bus |
| POST | `/admin/buses/{id}/reject` | Reject bus |
| GET | `/admin/bus-owners` | List owners |
| POST | `/admin/register-bus-owner` | Create owner account |
| POST | `/admin/register-bus` | Create approved+active bus |

---

## Health / web

| Method | Path | Description |
|--------|------|-------------|
| GET | `/up` | Health check |
| GET | `/` | Laravel welcome |

---

## Booking + PayHere example

```http
POST /api/bookings
Authorization: Bearer {token}
Content-Type: application/json

{
  "trip_id": 12,
  "seat_ids": [101, 102],
  "onboarding_location": "Kandy Bus Stand"
}
```

Response `201` includes `payhere` form fields (`merchant_id`, `hash`, `checkout_url`, …). The browser POSTs those fields to PayHere. Payment is confirmed only via `notify_url`.
