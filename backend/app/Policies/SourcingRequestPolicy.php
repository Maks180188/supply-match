<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class SourcingRequestPolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::Buyer;
    }
}
