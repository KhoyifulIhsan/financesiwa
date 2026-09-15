<?php

namespace App\Policies;

use App\Models\Bill;
use App\Models\User;

class BillPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Admin Keuangan']);
    }

    public function view(User $user, Bill $bill): bool
    {
        return $user->hasAnyRole(['Admin Keuangan']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Admin Keuangan']);
    }

    public function update(User $user, Bill $bill): bool
    {
        return $user->hasAnyRole(['Admin Keuangan']);
    }

    public function delete(User $user, Bill $bill): bool
    {
        return $user->hasAnyRole(['Admin Keuangan']);
    }
}
