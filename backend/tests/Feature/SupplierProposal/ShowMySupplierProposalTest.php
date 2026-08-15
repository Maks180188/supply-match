<?php

namespace Tests\Feature\SupplierProposal;

use App\Models\SupplierProposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowMySupplierProposalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_supplier_can_view_own_company_proposal(): void
    {
        $proposal = SupplierProposal::factory()->create();

        $user = User::factory()->supplier()->create([
            'company_id' => $proposal->company_id,
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson("/api/my/proposals/{$proposal->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $proposal->id)
            ->assertJsonPath('data.company_id', $proposal->company_id)
            ->assertJsonPath('data.sourcing_request.id', $proposal->sourcing_request_id)
            ->assertJsonPath('data.sourcing_request.company.id', $proposal->sourcingRequest->company_id)
            ->assertJsonPath('data.sourcing_request.category.id', $proposal->sourcingRequest->category_id)
            ->assertJsonStructure([
                'data' => [
                    'sourcing_request' => [
                        'keywords',
                    ],
                ],
            ]);
    }

    public function test_supplier_cannot_view_another_company_proposal(): void
    {
        $proposal = SupplierProposal::factory()->create();

        $user = User::factory()->supplier()->create();

        $this
            ->actingAs($user)
            ->getJson("/api/my/proposals/{$proposal->id}")
            ->assertForbidden();
    }

    public function test_guest_cannot_view_supplier_proposal(): void
    {
        $proposal = SupplierProposal::factory()->create();

        $this
            ->getJson("/api/my/proposals/{$proposal->id}")
            ->assertUnauthorized();
    }
}
