<?php

namespace App\Policies;

use App\Models\PartnerPayment;
use App\Models\User;

class PartnerPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Admin Keuangan', 'Akuntan', 'Manajer Keuangan', 'Direktur']);
    }

    public function view(User $user, PartnerPayment $partnerPayment): bool
    {
        return $user->hasAnyRole(['Admin Keuangan', 'Akuntan', 'Manajer Keuangan', 'Direktur']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Admin Keuangan', 'Akuntan', 'Manajer Keuangan']);
    }

    public function update(User $user, PartnerPayment $partnerPayment): bool
    {
        return $user->hasAnyRole(['Admin Keuangan', 'Akuntan', 'Manajer Keuangan']);
    }

    public function delete(User $user, PartnerPayment $partnerPayment): bool
    {
        return $user->hasAnyRole(['Admin Keuangan', 'Akuntan', 'Manajer Keuangan']);
    }
}
