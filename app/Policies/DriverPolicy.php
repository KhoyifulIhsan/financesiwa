<?php

namespace App\Policies;

use App\Models\Driver;
use App\Models\User;

class DriverPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Admin Operasional']);
    }

    public function view(User $user, Driver $driver): bool
    {
        return $user->hasAnyRole(['Admin Operasional']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Admin Operasional']);
    }

    public function update(User $user, Driver $driver): bool
    {
        return $user->hasAnyRole(['Admin Operasional']);
    }

    public function delete(User $user, Driver $driver): bool
    {
        return $user->hasAnyRole(['Admin Operasional']);
    }
}
