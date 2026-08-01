# BookKara API (`ticketing-system`)

Laravel API for **BookKara** — a Sri Lanka bus ticket booking platform. This backend powers search, seat booking, **PayHere** payments, tickets, bus-owner operations, and admin approval/ledger flows. The companion UI lives in `ticketing-system-ui`.

## Stack

| Layer | Choice |
|--------|--------|
| Runtime | PHP 8.3+ |
| Framework | Laravel 13 |
| Auth | Laravel Sanctum (Bearer tokens) |
| DB | SQLite by default; MySQL recommended for admin/owner analytics |
| Mail | Configurable (`log` by default) |
| Tests | PHPUnit |

## Roles

| Role | Purpose |
|------|---------|
| `passenger` | Search trips, book seats, view tickets |
| `bus_owner` | Manage buses/schedules, view bookings and revenue |
| `admin` | Approve buses, register owners, platform stats/ledger |

Public registration creates **passengers** only. Bus owners are created by admins.

## Core domain

```
Destination  →  Schedule (weekly template on a Bus)
                    ↓
                  Trip (date instance) → TripSeat inventory
                    ↓
                  Booking ↔ seats  →  Payment (fee split)
```

There is **no** separate Route model. A “route” is a schedule’s origin/destination pair.

## Booking flow

1. `GET /api/trips/locations` — destination catalog (EN / SI / TA)
2. `GET /api/trips?origin=&destination=&date=` — search (materializes trips/seats)
3. `GET /api/trips/{id}` — seat map
4. `POST /api/bookings` — reserve seats + return **PayHere** checkout fields (hash server-side)
5. Customer pays on PayHere → `POST /api/payments/payhere/notify` confirms payment
6. `GET /api/tickets/{bookingId}` — ticket payload; optional email resend

See [PayHere integration](docs/PAYHERE.md).

**Pricing:** customer pays base × **1.26** (20% service + 6% other). Owner payout ≈ base × 1.10; admin keeps ≈ base × 0.16.

## Quick start

```bash
cp .env.example .env
composer install
php artisan key:generate
# Ensure sqlite file exists if using default DB:
# touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

API base: `http://localhost:8000/api`

Optional: set `GOOGLE_CLIENT_ID` for Google Sign-In token verification.

### Useful commands

```bash
composer test          # or: php artisan test
composer run dev       # serve + queue + logs + vite
```

Seeded demo credentials (from `BusBookingSeeder`) typically use password `password123` — check the seeder for emails.

## Project layout

```
app/Http/Controllers/   Auth, Trip, Booking, Bus, Owner, Admin, Ticket
app/Jobs/               ProcessBooking (seat lock + payment row + emails)
app/Models/             User, Bus, Schedule, Trip, TripSeat, Booking, Payment, Destination
app/Repositories/       Bus / Trip / Booking repository interfaces + Eloquent
routes/api.php          All API endpoints
database/migrations/    Schema
docs/                   Architecture, API, domain reference
```

## Documentation

- [Architecture](docs/ARCHITECTURE.md)
- [API reference](docs/API.md)
- [Domain model](docs/DOMAIN.md)
- [AGENTS.md](AGENTS.md) — guidance for AI coding agents

## Related frontend

Point `NEXT_PUBLIC_API_URL` in `ticketing-system-ui` at this API (default `http://localhost:8000/api`).
