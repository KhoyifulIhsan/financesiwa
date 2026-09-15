<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Admin Keuangan']);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->hasAnyRole(['Admin Keuangan']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Admin Keuangan']);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->hasAnyRole(['Admin Keuangan']);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->hasAnyRole(['Admin Keuangan']);
    }
}
