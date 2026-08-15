<?php

namespace Tests\Feature\Admin;

use App\Enums\SourcingRequestStatus;
use App\Models\SourcingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListPendingSourcingRequestsTest extends TestCase
{
    use RefreshDatabase;
    public function test_admin_can_list_pending_sourcing_requests(): void
    {
        $admin = User::factory()->admin()->create();

        $pending = SourcingRequest::factory()->create([
            'status' => SourcingRequestStatus::PendingModeration,
        ]);

        SourcingRequest::factory()->create([
            'status' => SourcingRequestStatus::Draft,
        ]);

        SourcingRequest::factory()->published()->create();

        $response = $this
            ->actingAs($admin)
            ->getJson('/api/admin/sourcing-requests');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pending->id)
            ->assertJsonPath('data.0.status', SourcingRequestStatus::PendingModeration->value)
            ->assertJsonPath('data.0.company.id', $pending->company_id)
            ->assertJsonPath('data.0.category.id', $pending->category_id)
            ->assertJsonStructure([
                'data' => [
                    ['keywords'],
                ],
            ]);
    }

    public function test_non_admin_users_cannot_list_pending_sourcing_requests(): void
    {
        $buyer = User::factory()->buyer()->create();
        $supplier = User::factory()->supplier()->create();

        $this
            ->actingAs($buyer)
            ->getJson('/api/admin/sourcing-requests')
            ->assertForbidden();

        $this
            ->actingAs($supplier)
            ->getJson('/api/admin/sourcing-requests')
            ->assertForbidden();
    }

    public function test_guest_cannot_list_pending_sourcing_requests(): void
    {
        $this
            ->getJson('/api/admin/sourcing-requests')
            ->assertUnauthorized();
    }
}
