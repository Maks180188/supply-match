<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Supplier;
    }
}
