<?php

namespace Tests\Feature\Admin;

use App\Models\SourcingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowSourcingRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_sourcing_request(): void
    {
        $admin = User::factory()->admin()->create();

        $sourcingRequest = SourcingRequest::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->getJson("/api/admin/sourcing-requests/{$sourcingRequest->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $sourcingRequest->id)
            ->assertJsonPath('data.company.id', $sourcingRequest->company_id)
            ->assertJsonPath('data.category.id', $sourcingRequest->category_id)
            ->assertJsonStructure([
                'data' => [
                    'keywords',
                ],
            ]);
    }

    public function test_non_admin_users_cannot_view_sourcing_request(): void
    {
        $sourcingRequest = SourcingRequest::factory()->create();

        $buyer = User::factory()->buyer()->create();
        $supplier = User::factory()->supplier()->create();

        $this
            ->actingAs($buyer)
            ->getJson("/api/admin/sourcing-requests/{$sourcingRequest->id}")
            ->assertForbidden();

        $this
            ->actingAs($supplier)
            ->getJson("/api/admin/sourcing-requests/{$sourcingRequest->id}")
            ->assertForbidden();
    }

    public function test_guest_cannot_view_sourcing_request(): void
    {
        $sourcingRequest = SourcingRequest::factory()->create();

        $this
            ->getJson("/api/admin/sourcing-requests/{$sourcingRequest->id}")
            ->assertUnauthorized();
    }
}
