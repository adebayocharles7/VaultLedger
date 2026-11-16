<?php

namespace App\Policies;

use App\Models\Remittance;
use App\Models\User;

class RemittancePolicy
{
    public function view(User $user, Remittance $remittance): bool
    {
        return $user->id === $remittance->sourceFolio?->user_id
            || $user->id === $remittance->destinationFolio?->user_id;
    }

    public function update(User $user, Remittance $remittance): bool
    {
        // Only the sender can reverse a transfer.
        return $user->id === $remittance->sourceFolio?->user_id;
    }
}