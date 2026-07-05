<?php

namespace App\Repositories\Contracts;

interface BusRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function create(array $data);

    /**
     * Find a bus by ID.
     */
    public function findById(int $id);

    /**
     * Update a bus record.
     */
    public function update(int $id, array $data);
}