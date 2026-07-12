<!DOCTYPE html>
<html>
<head>
    <title>New Booking Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .brand-logo {
            width: 180px;
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto 10px;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
        }
        .info-box {
            background: white;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #3b82f6;
            border-radius: 4px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: bold;
            color: #6b7280;
        }
        .value {
            color: #111827;
            font-weight: 600;
        }
        .alert {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    @php $isOwner = ($recipientType ?? 'passenger') === 'owner'; @endphp

    <div class="header">
        <img src="{{ asset('bookkara-logo.svg') }}" alt="BookKara" class="brand-logo" />
        <h1>{{ $isOwner ? '🚌 New Booking Alert' : '🎫 Booking Confirmed' }}</h1>
        <p>{{ $isOwner ? 'A passenger completed a booking for your bus' : 'Your BookKara ticket is ready' }}</p>
    </div>

    <div class="content">
        @if($isOwner)
            <div class="alert">
                <strong>⚠️ Action Required:</strong> A new booking was completed. Please prepare your bus and boarding point for this trip.
            </div>
        @else
            <div class="alert" style="background:#dcfce7;border-color:#22c55e;">
                <strong>✅ Success:</strong> Your booking has been confirmed. Please keep your ticket reference for travel.
            </div>
        @endif

        <div class="info-box">
            <h3>Bus Information</h3>
            <div class="info-row">
                <span class="label">Bus Number Plate:</span>
                <span class="value">{{ $bookingDetails['bus_number_plate'] }}</span>
            </div>
            <div class="info-row">
                <span class="label">Bus Model:</span>
                <span class="value">{{ $bookingDetails['bus_model'] }}</span>
            </div>
        </div>

        <div class="info-box">
            <h3>Trip Details</h3>
            <div class="info-row">
                <span class="label">Route:</span>
                <span class="value">{{ $bookingDetails['origin'] }} → {{ $bookingDetails['destination'] }}</span>
            </div>
            <div class="info-row">
                <span class="label">Departure Date:</span>
                <span class="value">{{ $bookingDetails['departure_date'] }}</span>
            </div>
            <div class="info-row">
                <span class="label">Departure Time:</span>
                <span class="value">{{ $bookingDetails['departure_time'] }}</span>
            </div>
        </div>

        <div class="info-box">
            <h3>Passenger Information</h3>
            @if($bookingDetails['passenger_name'] ?? null)
            <div class="info-row">
                <span class="label">Passenger Name:</span>
                <span class="value">{{ $bookingDetails['passenger_name'] }}</span>
            </div>
            @endif
            <div class="info-row">
                <span class="label">Onboarding Location:</span>
                <span class="value">{{ $bookingDetails['onboarding_location'] }}</span>
            </div>
            <div class="info-row">
                <span class="label">Seat Numbers:</span>
                <span class="value">{{ implode(', ', $bookingDetails['seat_numbers']) }}</span>
            </div>
            <div class="info-row">
                <span class="label">Number of Passengers:</span>
                <span class="value">{{ $bookingDetails['ticket_count'] }}</span>
            </div>
            <div class="info-row">
                <span class="label">Booking Reference:</span>
                <span class="value">{{ $bookingDetails['ticket_reference'] }}</span>
            </div>
            <div class="info-row">
                <span class="label">Total Price:</span>
                <span class="value">LKR {{ number_format((float) ($bookingDetails['total_price'] ?? 0), 2) }}</span>
            </div>
        </div>

        @if($isOwner)
            <p style="margin-top: 20px;">
                <strong>Important:</strong> Please be at <strong>{{ $bookingDetails['onboarding_location'] }}</strong> on time to pick up passengers.
            </p>
        @else
            <p style="margin-top: 20px;">
                <strong>Travel Note:</strong> Reach <strong>{{ $bookingDetails['onboarding_location'] }}</strong> at least 15 minutes before departure.
            </p>
        @endif
    </div>

    <div class="footer">
        <p><strong>BookKara</strong> - Your Trusted Travel Partner</p>
        <p>This is an automated notification. Please do not reply to this email.</p>
    </div>
</body>
</html>
