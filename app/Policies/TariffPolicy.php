<?php

namespace App\Policies;

use App\Models\Tariff;
use App\Models\User;

class TariffPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Admin Keuangan', 'Admin Operasional']);
    }

    public function view(User $user, Tariff $tariff): bool
    {
        return $user->hasAnyRole(['Admin Keuangan', 'Admin Operasional']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Admin Keuangan', 'Admin Operasional']);
    }

    public function update(User $user, Tariff $tariff): bool
    {
        return $user->hasAnyRole(['Admin Keuangan', 'Admin Operasional']);
    }

    public function delete(User $user, Tariff $tariff): bool
    {
        return $user->hasAnyRole(['Admin Keuangan', 'Admin Operasional']);
    }
}
