<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;

class TripPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Admin Operasional', 'Admin Keuangan']);
    }

    public function view(User $user, Trip $trip): bool
    {
        return $user->hasAnyRole(['Admin Operasional', 'Admin Keuangan']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Admin Operasional']);
    }

    public function update(User $user, Trip $trip): bool
    {
        return $user->hasAnyRole(['Admin Operasional', 'Admin Keuangan']);
    }

    public function delete(User $user, Trip $trip): bool
    {
        return $user->hasAnyRole(['Admin Operasional']);
    }
}
