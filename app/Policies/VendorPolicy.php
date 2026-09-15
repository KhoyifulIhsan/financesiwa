<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

class VendorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Admin Keuangan']);
    }

    public function view(User $user, Vendor $vendor): bool
    {
        return $user->hasAnyRole(['Admin Keuangan']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Admin Keuangan']);
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $user->hasAnyRole(['Admin Keuangan']);
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return $user->hasAnyRole(['Admin Keuangan']);
    }
}
