<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\SourcingRequest;
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

    public function submit(User $user, SourcingRequest $sourcingRequest): bool
    {
        return $user->role === UserRole::Buyer && $user->company_id === $sourcingRequest->company_id;
    }

    public function publish(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function reject(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    // buyer list
    public function viewOwn(User $user): bool
    {
        return $user->role === UserRole::Buyer;
    }

    // buyer detail
    public function view(User $user, SourcingRequest $sourcingRequest): bool
    {
        return $user->role === UserRole::Buyer && $user->company_id === $sourcingRequest->company_id;
    }

    public function update(User $user, SourcingRequest $sourcingRequest): bool
    {
        return $user->role === UserRole::Buyer && $user->company_id === $sourcingRequest->company_id;
    }

    public function propose(User $user, SourcingRequest $sourcingRequest): bool
    {
        return $user->role === UserRole::Supplier;
    }

    public function viewProposals(User $user, SourcingRequest $sourcingRequest): bool
    {
        return $user->role === UserRole::Buyer && $user->company_id === $sourcingRequest->company_id;
    }

    // admin list
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    // admin detail
    public function viewAdmin(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }
}
