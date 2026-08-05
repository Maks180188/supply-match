<?php

namespace Tests\Feature\SupplierProposal;

use App\Enums\SourcingRequestStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\SourcingRequest;
use App\Models\SupplierProposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ListSourcingRequestProposalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_buyer_can_view_proposals_for_own_sourcing_request(): void
    {
        $buyerCompany = Company::factory()->buyer()->create();

        $buyer = User::factory()->create([
            'company_id' => $buyerCompany->id,
            'role' => UserRole::Buyer,
        ]);

        $sourcingRequest = SourcingRequest::factory()->create([
            'company_id' => $buyerCompany->id,
            'created_by' => $buyer->id,
            'status' => SourcingRequestStatus::Archived,
            'published_at' => now()->subDay(),
        ]);

        $olderProposal = SupplierProposal::factory()->create([
            'sourcing_request_id' => $sourcingRequest->id,
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        $newerProposal = SupplierProposal::factory()->create([
            'sourcing_request_id' => $sourcingRequest->id,
        ]);

        $response = $this
            ->actingAs($buyer)
            ->getJson("/api/my/sourcing-requests/{$sourcingRequest->id}/proposals");

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $newerProposal->id)
            ->assertJsonPath('data.0.company.id', $newerProposal->company_id)
            ->assertJsonPath('data.1.id', $olderProposal->id)
            ->assertJsonPath('data.1.company.id', $olderProposal->company_id)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    #[DataProvider('unauthorizedUsersProvider')]
    public function test_unauthorized_users_cannot_view_proposals(?UserRole $role, int $expectedStatus): void
    {
        $buyerCompany = Company::factory()->buyer()->create();

        $buyer = User::factory()->create([
            'company_id' => $buyerCompany->id,
            'role' => UserRole::Buyer,
        ]);

        $sourcingRequest = SourcingRequest::factory()->create([
            'company_id' => $buyerCompany->id,
            'created_by' => $buyer->id,
            'status' => SourcingRequestStatus::Archived,
            'published_at' => now()->subDay(),
        ]);

        SupplierProposal::factory()->create([
            'sourcing_request_id' => $sourcingRequest->id,
        ]);

        if ($role !== null) {
            $user = match ($role) {
                UserRole::Buyer => User::factory()->buyer()->create(),
                UserRole::Supplier => User::factory()->supplier()->create(),
                default => throw new LogicException('Unsupported user role.'),
            };

            $this->actingAs($user);
        }

        $this->getJson("/api/my/sourcing-requests/{$sourcingRequest->id}/proposals")
            ->assertStatus($expectedStatus);
    }

    public static function unauthorizedUsersProvider(): array
    {
        return [
            'buyer from another company' => [UserRole::Buyer, 403],
            'supplier' => [UserRole::Supplier, 403],
            'guest' => [null, 401],
        ];
    }
}
