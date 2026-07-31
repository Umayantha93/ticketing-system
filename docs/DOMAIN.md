# Domain model — BookKara

## Entities

### User
Roles: `admin` | `bus_owner` | `passenger` (default).  
Owners may have `service_name`, phone, and bank fields. Passengers book via `bookings`.

### Bus
Owned by a `bus_owner` (`user_id`). Layout: `1x2` | `2x2` | `1x3` | `2x3` plus `last_row_seats`.  
`approval_status`: `pending` | `approved` | `rejected`.  
`status`: `active` | `inactive`. Unapproved buses are forced inactive. Public search uses approved + active only.

### Schedule
Weekly template for a bus: day of week, times, origin/destination (English strings + optional destination FKs), price. Unique per bus + corridor + day + departure time.

### Trip
Concrete run of a schedule on a `departure_date`. Status: `scheduled` | `delayed` | `completed` | `cancelled`. Often created lazily during search.

### TripSeat
Per-trip inventory. `seat_number` like `A1`. Status: `available` | `reserved` | `booked`. Synced from bus layout when trips are shown/searched.

### Booking
Passenger purchase: trip, seats (M2M `booking_seat`), `ticket_reference`, counts/price, `onboarding_location`.  
Status: `pending` | `confirmed` | `cancelled`.  
`payment_status`: `pending` | `paid` | `failed` | `refunded`. Booking job sets payment to paid.

### Payment
1:1 with booking. Stores gross, base fare, service/other charges, owner payout, admin shares, method, status, `paid_at`, meta.

### Destination
Sri Lanka places with `name_en` / `name_si` / `name_ta`, aliases, district code. Search resolves multilingual input.

## Pricing (keep in sync with UI)

| Constant | Value |
|----------|-------|
| Service charge | 20% of base |
| Other charge | 6% of base |
| Customer total | base × **1.26** |
| Owner payout | base + half service ≈ base × **1.10** |
| Admin share | half service + other ≈ base × **0.16** |

Fee math is duplicated in several PHP classes and in the UI — change all call sites together.

## Status cheat sheet

| Entity | Important values |
|--------|------------------|
| Bus approval | pending → approved / rejected |
| Seat | available → booked (via booking job) |
| Booking payment | paid immediately on successful book |
| Trip | scheduled (default for new search trips) |

## Seat capacity rule (UI-enforced)

Frontend limits selection to **70%** of bus capacity. Backend still validates seat availability only — do not assume backend enforces the 70% cap unless you add it.
