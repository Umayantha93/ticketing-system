<!DOCTYPE html>
<html>
<head>
    <title>BookKara Ticket</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.5;
            color: #1f2937;
            max-width: 620px;
            margin: 0 auto;
            padding: 20px;
            background: #f3f4f6;
        }
        .ticket {
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            overflow: hidden;
        }
        .ticket-header {
            background: #0f172a;
            color: #ffffff;
            padding: 16px 20px;
        }
        .ticket-header h1 {
            margin: 0;
            font-size: 20px;
        }
        .ticket-header p {
            margin: 6px 0 0;
            font-size: 13px;
            opacity: 0.9;
        }
        .ref-badge {
            display: inline-block;
            margin-top: 10px;
            background: #22c55e;
            color: #052e16;
            font-weight: 700;
            font-size: 12px;
            padding: 5px 10px;
            border-radius: 999px;
        }
        .ticket-body {
            padding: 20px;
        }
        .row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px dashed #e5e7eb;
        }
        .row:last-child {
            border-bottom: none;
        }
        .label {
            color: #6b7280;
            font-weight: 700;
            font-size: 13px;
        }
        .value {
            color: #111827;
            font-weight: 700;
            font-size: 14px;
            text-align: right;
        }
        .ticket-footer {
            padding: 12px 20px 18px;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    @php
        $isOwner = ($recipientType ?? 'passenger') === 'owner';
        $seatNumbers = implode(', ', $bookingDetails['seat_numbers'] ?? []);
        $departureDateTime = trim(($bookingDetails['departure_date'] ?? '') . ' ' . ($bookingDetails['departure_time'] ?? ''));
    @endphp

    <div class="ticket">
        <div class="ticket-header">
            <h1>{{ $isOwner ? 'Bus Owner Booking Ticket' : 'Passenger Bus Ticket' }}</h1>
            <p>{{ $isOwner ? 'A new confirmed booking for your bus' : 'Your booking is confirmed' }}</p>
            <span class="ref-badge">Booking Ref: {{ $bookingDetails['ticket_reference'] ?? 'N/A' }}</span>
        </div>

        <div class="ticket-body">
            <div class="row">
                <span class="label">Bus Number Plate</span>
                <span class="value">{{ $bookingDetails['bus_number_plate'] ?? 'N/A' }}</span>
            </div>
            <div class="row">
                <span class="label">Route</span>
                <span class="value">{{ ($bookingDetails['origin'] ?? 'N/A') . ' -> ' . ($bookingDetails['destination'] ?? 'N/A') }}</span>
            </div>
            <div class="row">
                <span class="label">Departure Date & Time</span>
                <span class="value">{{ $departureDateTime !== '' ? $departureDateTime : 'N/A' }}</span>
            </div>
            <div class="row">
                <span class="label">Passenger Name</span>
                <span class="value">{{ $bookingDetails['passenger_name'] ?? 'N/A' }}</span>
            </div>
            <div class="row">
                <span class="label">Bus Phone Number</span>
                <span class="value">{{ $bookingDetails['transport_contact_number'] ?? 'N/A' }}</span>
            </div>
            <div class="row">
                <span class="label">Number of Passengers</span>
                <span class="value">{{ $bookingDetails['ticket_count'] ?? '0' }}</span>
            </div>
            <div class="row">
                <span class="label">Seat Numbers</span>
                <span class="value">{{ $seatNumbers !== '' ? $seatNumbers : 'N/A' }}</span>
            </div>
            <div class="row">
                <span class="label">Total Price</span>
                <span class="value">LKR {{ number_format((float) ($bookingDetails['total_price'] ?? 0), 2) }}</span>
            </div>
        </div>

        <div class="ticket-footer">
            This is an automated ticket email from BookKara.
        </div>
    </div>
</body>
</html>
