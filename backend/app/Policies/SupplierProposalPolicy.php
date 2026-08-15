<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\SupplierProposal;
use App\Models\User;

class SupplierProposalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Supplier;
    }

    public function view(User $user, SupplierProposal $supplierProposal): bool
    {
        return $user->role === UserRole::Supplier && $user->company_id === $supplierProposal->company_id;
    }
}
