<?php

namespace App\Repositories\Contracts;

interface AuthRepositoryInterface
{
    public function createUser(array $data);
    public function findByEmail(string $email);
}