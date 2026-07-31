# Agent guidance — BookKara API

This is the **Laravel API** for BookKara bus ticket booking. Pair with `ticketing-system-ui` (Next.js).

## Before changing code

1. Read `README.md` and `docs/DOMAIN.md` for domain meaning.
2. Keep fee constants in sync across `BookingController`, `ProcessBooking`, `AdminController`, `OwnerController`, seeder, and the UI (`lib/utils.ts`): **20% service + 6% other**.
3. Prefer matching existing patterns: inline `$request->validate()`, ad-hoc JSON responses, Sanctum + `role:` middleware.

## Architecture rules

- Controllers stay thin for Bus/Trip/Booking via repositories where already wired.
- Admin/Owner/Ticket may query models directly (existing pattern).
- Do **not** invent Form Requests / API Resources unless the task explicitly asks; current code uses none.
- Roles are string values: `passenger`, `bus_owner`, `admin` — never invent aliases like `owner` in API/middleware.

## Booking / seats

- Seat booking must remain transactional (lock seats, reject if not `available`).
- Payment is **simulated at booking time** — there is no payment-gateway endpoint. Do not assume a separate pay step exists.
- Ticket URL id is the **booking id**, not `ticket_reference`.
- Trip search/show may `firstOrCreate` trips and sync seats — GET has side effects by design.

## Database notes

- Default env uses SQLite; admin/owner **stats/ledger SQL uses MySQL functions**. Prefer MySQL when working on those endpoints.
- Buses need `approval_status=approved` and `status=active` to appear in public search.

## Docs map

| File | Contents |
|------|----------|
| `docs/ARCHITECTURE.md` | Layers, auth, jobs |
| `docs/API.md` | Endpoint catalog |
| `docs/DOMAIN.md` | Models, pricing, statuses |
| `.cursor/rules/` | Cursor rules for this package |
