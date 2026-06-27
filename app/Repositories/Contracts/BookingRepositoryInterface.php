<?php

namespace App\Repositories\Contracts;

interface BookingRepositoryInterface
{
    public function getTripPriceAndSeats($tripId);
    public function getPassengerBookings($userId);
}