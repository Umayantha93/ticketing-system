# Architecture — BookKara API

## System context

```
ticketing-system-ui (Next.js)
        │  HTTPS / JSON  Bearer Sanctum token
        ▼
ticketing-system (Laravel)  /api/*
        │
        ▼
   SQLite / MySQL
```

Brand / product name in emails and UI: **BookKara**.

## Request pipeline

1. Routes in `routes/api.php` (prefix `/api` from Laravel bootstrap).
2. `auth:sanctum` for authenticated groups.
3. `role:{passenger|bus_owner|admin}` via `RoleMiddleware` (403 `{ "message": "Unauthorized" }`).
4. Controllers validate with `$request->validate()` and return `response()->json(...)`.
5. `api/*` responses are forced JSON in `bootstrap/app.php`.

## Layers

| Layer | Location | Notes |
|-------|----------|--------|
| HTTP | `app/Http/Controllers` | Primary entry |
| Middleware | `app/Http/Middleware/RoleMiddleware` | Role gate |
| Jobs | `app/Jobs/ProcessBooking` | Booking transaction; often `dispatchSync` |
| Mail | `app/Mail/BookingNotificationMail` | Passenger + owner notification |
| Domain | `app/Models` | Eloquent models |
| Persistence | `app/Repositories` | Interfaces + Eloquent for Bus, Trip, Booking |

No Policies, Form Requests, or API Resources in the current codebase.

## Auth

- Users implement Sanctum `HasApiTokens`.
- Login / register / Google return a personal access token named `auth_token`.
- Google: verify `id_token` against Google tokeninfo using `GOOGLE_CLIENT_ID`.
- Client sends `Authorization: Bearer {token}`.

## Booking transaction (`ProcessBooking`)

Inside a DB transaction:

1. Lock selected `trip_seats`.
2. Fail if any seat is not `available`.
3. Create `bookings` row (`payment_status=paid`, generate `ticket_reference`).
4. Create `payments` row (card, paid) with fee breakdown.
5. Mark seats `booked` and attach `booking_seat` pivot.
6. Queue/send booking notification emails.

There is **no** external payment provider — success is assumed when the booking job completes.

## Repository usage

Bound in `AppServiceProvider`:

- `BusRepositoryInterface` → Eloquent
- `TripRepositoryInterface` → Eloquent (includes seat layout sync)
- `BookingRepositoryInterface` → Eloquent

Prefer extending these for Bus/Trip/Booking persistence rather than scattering new queries.

## Analytics caveat

`AdminController` / `OwnerController` aggregates often use MySQL-specific SQL (`DATE_FORMAT`, `WEEK`, etc.). Running those against SQLite may fail even though the default `.env.example` uses sqlite.
