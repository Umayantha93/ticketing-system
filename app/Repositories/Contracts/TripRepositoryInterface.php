<?php

namespace App\Repositories\Contracts;

interface TripRepositoryInterface
{
    public function searchTrips($origin, $destination, $date);
    public function getTripWithSeats($id);
    public function createSchedule(array $data);
}