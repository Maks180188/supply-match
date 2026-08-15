<?php

namespace Tests\Feature\SupplierProposal;

use App\Models\SupplierProposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowSourcingRequestProposalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_buyer_can_view_proposal_for_own_sourcing_request(): void
    {
        $proposal = SupplierProposal::factory()->create();

        $buyer = User::factory()->buyer()->create([
            'company_id' => $proposal->sourcingRequest->company_id,
        ]);

        $response = $this
            ->actingAs($buyer)
            ->getJson("/api/my/sourcing-requests/{$proposal->sourcing_request_id}/proposals/{$proposal->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $proposal->id)
            ->assertJsonPath('data.sourcing_request_id', $proposal->sourcing_request_id)
            ->assertJsonPath('data.company.id', $proposal->company_id)
            ->assertJsonPath('data.sourcing_request.id', $proposal->sourcing_request_id);
    }

    public function test_buyer_cannot_view_proposal_for_another_company_sourcing_request(): void
    {
        $proposal = SupplierProposal::factory()->create();

        $buyer = User::factory()->buyer()->create();

        $this
            ->actingAs($buyer)
            ->getJson("/api/my/sourcing-requests/{$proposal->sourcing_request_id}/proposals/{$proposal->id}")
            ->assertForbidden();
    }

    public function test_proposal_from_another_sourcing_request_returns_not_found(): void
    {
        $proposal = SupplierProposal::factory()->create();

        $anotherProposal = SupplierProposal::factory()->create();

        $buyer = User::factory()->buyer()->create([
            'company_id' => $proposal->sourcingRequest->company_id,
        ]);

        $this
            ->actingAs($buyer)
            ->getJson("/api/my/sourcing-requests/{$proposal->sourcing_request_id}/proposals/{$anotherProposal->id}")
            ->assertNotFound();
    }

    public function test_guest_cannot_view_sourcing_request_proposal(): void
    {
        $proposal = SupplierProposal::factory()->create();

        $this
            ->getJson("/api/my/sourcing-requests/{$proposal->sourcing_request_id}/proposals/{$proposal->id}")
            ->assertUnauthorized();
    }
}
