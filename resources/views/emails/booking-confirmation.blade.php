<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .ticket-container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .ticket-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .ticket-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .ticket-header p {
            margin: 10px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .ticket-body {
            padding: 30px;
        }
        .ticket-reference {
            background-color: #f8fafc;
            border: 2px dashed #1e3a8a;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
        }
        .ticket-reference-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        .ticket-reference-value {
            font-size: 24px;
            font-weight: bold;
            color: #1e3a8a;
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
        }
        .ticket-details {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #64748b;
            font-size: 14px;
        }
        .detail-value {
            font-weight: 600;
            color: #1e293b;
            font-size: 14px;
            text-align: right;
        }
        .journey-info {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 20px;
        }
        .journey-route {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .journey-location {
            flex: 1;
            text-align: center;
        }
        .journey-location-label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        .journey-location-name {
            font-size: 18px;
            font-weight: bold;
            color: #1e293b;
        }
        .journey-arrow {
            flex: 0 0 60px;
            text-align: center;
            color: #3b82f6;
            font-size: 20px;
        }
        .journey-meta {
            display: flex;
            justify-content: space-around;
            padding-top: 15px;
            border-top: 1px solid #cbd5e1;
        }
        .journey-meta-item {
            text-align: center;
        }
        .journey-meta-label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .journey-meta-value {
            font-size: 14px;
            font-weight: bold;
            color: #1e293b;
            margin-top: 3px;
        }
        .seats-info {
            background-color: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .seats-label {
            font-size: 12px;
            color: #1e40af;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .seats-value {
            font-size: 18px;
            font-weight: bold;
            color: #1e3a8a;
        }
        .price-summary {
            background-color: #ecfdf5;
            border: 2px solid #10b981;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .price-row:last-child {
            margin-bottom: 0;
            padding-top: 10px;
            border-top: 2px solid #10b981;
        }
        .price-label {
            font-size: 14px;
            color: #047857;
        }
        .price-value {
            font-size: 14px;
            font-weight: 600;
            color: #047857;
        }
        .total-label {
            font-size: 16px;
            font-weight: bold;
            color: #065f46;
        }
        .total-value {
            font-size: 20px;
            font-weight: bold;
            color: #065f46;
        }
        .instructions {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .instructions h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #92400e;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .instructions ul {
            margin: 0;
            padding-left: 20px;
        }
        .instructions li {
            margin-bottom: 5px;
            font-size: 13px;
            color: #78350f;
        }
        .footer {
            text-align: center;
            padding: 20px;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #64748b;
        }
        .print-button {
            display: block;
            width: 100%;
            background-color: #3b82f6;
            color: white;
            text-align: center;
            padding: 15px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        @media print {
            body {
                background-color: white;
            }
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <!-- Header -->
        <div class="ticket-header">
            <h1>🚌 Lanka Express</h1>
            <p>Your Booking is Confirmed!</p>
        </div>

        <!-- Body -->
        <div class="ticket-body">
            <!-- Ticket Reference -->
            <div class="ticket-reference">
                <div class="ticket-reference-label">Booking Reference</div>
                <div class="ticket-reference-value">{{ $booking->ticket_reference }}</div>
            </div>

            <!-- Journey Information -->
            <div class="journey-info">
                <div class="journey-route">
                    <div class="journey-location">
                        <div class="journey-location-label">From</div>
                        <div class="journey-location-name">{{ $booking->trip->schedule->origin }}</div>
                    </div>
                    <div class="journey-arrow">→</div>
                    <div class="journey-location">
                        <div class="journey-location-label">To</div>
                        <div class="journey-location-name">{{ $booking->trip->schedule->destination }}</div>
                    </div>
                </div>
                <div class="journey-meta">
                    <div class="journey-meta-item">
                        <div class="journey-meta-label">Date</div>
                        <div class="journey-meta-value">{{ \Carbon\Carbon::parse($booking->trip->departure_date)->format('M d, Y') }}</div>
                    </div>
                    <div class="journey-meta-item">
                        <div class="journey-meta-label">Departure</div>
                        <div class="journey-meta-value">{{ \Carbon\Carbon::parse($booking->trip->schedule->departure_time)->format('h:i A') }}</div>
                    </div>
                    <div class="journey-meta-item">
                        <div class="journey-meta-label">Arrival</div>
                        <div class="journey-meta-value">{{ \Carbon\Carbon::parse($booking->trip->schedule->estimated_arrival_time)->format('h:i A') }}</div>
                    </div>
                </div>
            </div>

            <!-- Bus Details -->
            <div class="ticket-details">
                <div class="detail-row">
                    <span class="detail-label">Bus Number</span>
                    <span class="detail-value">{{ $booking->trip->schedule->bus->bus_number_plate }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Bus Model</span>
                    <span class="detail-value">{{ $booking->trip->schedule->bus->model }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Layout Type</span>
                    <span class="detail-value">{{ $booking->trip->schedule->bus->layout_type }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Passenger Name</span>
                    <span class="detail-value">{{ $booking->passenger->name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Contact</span>
                    <span class="detail-value">{{ $booking->passenger->phone_number }}</span>
                </div>
            </div>

            <!-- Seat Information -->
            <div class="seats-info">
                <div class="seats-label">Your Seat Numbers</div>
                <div class="seats-value">
                    {{ $booking->seats->pluck('seat_number')->join(', ') }}
                </div>
            </div>

            <!-- Price Summary -->
            <div class="price-summary">
                <div class="price-row">
                    <span class="price-label">Number of Seats</span>
                    <span class="price-value">{{ $booking->ticket_count }}</span>
                </div>
                <div class="price-row">
                    <span class="price-label">Price per Seat</span>
                    <span class="price-value">LKR {{ number_format($booking->total_price / $booking->ticket_count, 2) }}</span>
                </div>
                <div class="price-row">
                    <span class="total-label">Total Amount</span>
                    <span class="total-value">LKR {{ number_format($booking->total_price, 2) }}</span>
                </div>
            </div>

            <!-- Instructions -->
            <div class="instructions">
                <h3>⚠️ Important Instructions</h3>
                <ul>
                    <li>Please arrive at the departure point 15 minutes before scheduled departure.</li>
                    <li>Show this ticket (digital or printed) to the bus conductor.</li>
                    <li>Your booking reference is: <strong>{{ $booking->ticket_reference }}</strong></li>
                    <li>Please carry a valid ID proof for verification.</li>
                    <li>Seats are non-transferable and non-refundable.</li>
                </ul>
            </div>

            <!-- Print Button -->
            <a href="#" onclick="window.print(); return false;" class="print-button">
                🖨️ Print This Ticket
            </a>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Lanka Express</strong> - Your Trusted Travel Partner</p>
            <p>For support, contact us at support@lankaexpress.com</p>
            <p>Booking Date: {{ $booking->created_at->format('M d, Y h:i A') }}</p>
        </div>
    </div>
</body>
</html>
