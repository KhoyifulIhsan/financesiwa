<?php

namespace App\Policies;

use App\Models\Coa;
use App\Models\User;

class CoaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Akuntan']);
    }

    public function view(User $user, Coa $coa): bool
    {
        return $user->hasAnyRole(['Akuntan']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Akuntan']);
    }

    public function update(User $user, Coa $coa): bool
    {
        return $user->hasAnyRole(['Akuntan']);
    }

    public function delete(User $user, Coa $coa): bool
    {
        return $user->hasAnyRole(['Akuntan']);
    }
}
