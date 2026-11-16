<?php

namespace App\Policies;

use App\Models\Folio;
use App\Models\User;

class FolioPolicy
{
    public function view(User $user, Folio $folio): bool
    {
        return $user->id === $folio->user_id;
    }

    public function update(User $user, Folio $folio): bool
    {
        return $user->id === $folio->user_id;
    }

    public function delete(User $user, Folio $folio): bool
    {
        return $user->id === $folio->user_id;
    }
}