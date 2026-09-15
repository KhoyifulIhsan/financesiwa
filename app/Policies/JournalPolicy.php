<?php

namespace App\Policies;

use App\Models\Journal;
use App\Models\User;

class JournalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Akuntan']);
    }

    public function view(User $user, Journal $journal): bool
    {
        return $user->hasAnyRole(['Akuntan']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Akuntan']);
    }

    public function update(User $user, Journal $journal): bool
    {
        return $user->hasAnyRole(['Akuntan']);
    }

    public function delete(User $user, Journal $journal): bool
    {
        return $user->hasAnyRole(['Akuntan']);
    }
}
