<?php

namespace App\Repositories\Contracts;

interface BusRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function create(array $data);
}