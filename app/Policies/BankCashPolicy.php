<?php

namespace App\Policies;

use App\Models\BankCash;
use App\Models\User;

class BankCashPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Admin Keuangan', 'Akuntan']);
    }

    public function view(User $user, BankCash $bankCash): bool
    {
        return $user->hasAnyRole(['Admin Keuangan', 'Akuntan']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Admin Keuangan', 'Akuntan']);
    }

    public function update(User $user, BankCash $bankCash): bool
    {
        return $user->hasAnyRole(['Admin Keuangan', 'Akuntan']);
    }

    public function delete(User $user, BankCash $bankCash): bool
    {
        return $user->hasAnyRole(['Admin Keuangan', 'Akuntan']);
    }
}
