<?php

namespace App\Repositories\Eloquent;

use App\Models\Bus;
use App\Repositories\Contracts\BusRepositoryInterface;

class BusRepository implements BusRepositoryInterface
{
    public function create(array $data)
    {
        // Implement the logic to create a new bus record in the database
        // For example, you can use Eloquent's create method:
        return Bus::create($data);
    }
}