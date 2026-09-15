<?php

namespace App\Policies;

use App\Models\Investor;
use App\Models\User;

class InvestorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Admin Operasional', 'Admin Keuangan']);
    }

    public function view(User $user, Investor $investor): bool
    {
        return $user->hasAnyRole(['Admin Operasional', 'Admin Keuangan']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Admin Operasional', 'Admin Keuangan']);
    }

    public function update(User $user, Investor $investor): bool
    {
        return $user->hasAnyRole(['Admin Operasional', 'Admin Keuangan']);
    }

    public function delete(User $user, Investor $investor): bool
    {
        return $user->hasAnyRole(['Admin Operasional', 'Admin Keuangan']);
    }
}
