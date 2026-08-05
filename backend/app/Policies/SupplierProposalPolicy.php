<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class SupplierProposalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Supplier;
    }
}
