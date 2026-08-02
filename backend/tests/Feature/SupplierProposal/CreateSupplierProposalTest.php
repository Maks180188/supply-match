<?php

namespace Tests\Feature\SupplierProposal;

use App\Enums\SourcingRequestStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\SourcingRequest;
use App\Models\SupplierProposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreateSupplierProposalTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_can_submit_proposal_for_published_sourcing_request(): void
    {
        $supplier = User::factory()->supplier()->create();
        $sourcingRequest = SourcingRequest::factory()->published()->create();

        Sanctum::actingAs($supplier);

        $response = $this->postJson(
            "/api/sourcing-requests/{$sourcingRequest->id}/proposals",
            [
                'amount' => 12500.50,
                'currency' => 'USD',
                'delivery_days' => 14,
                'message' => 'We can deliver the requested equipment.',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.sourcing_request_id', $sourcingRequest->id)
            ->assertJsonPath('data.company_id', $supplier->company_id)
            ->assertJsonPath('data.created_by', $supplier->id)
            ->assertJsonPath('data.amount', '12500.50')
            ->assertJsonPath('data.currency', 'USD')
            ->assertJsonPath('data.delivery_days', 14)
            ->assertJsonPath(
                'data.message',
                'We can deliver the requested equipment.'
            );

        $supplierProposal = SupplierProposal::query()->sole();

        $this->assertSame($sourcingRequest->id, $supplierProposal->sourcing_request_id);
        $this->assertSame($supplier->company_id, $supplierProposal->company_id);
        $this->assertSame($supplier->id, $supplierProposal->created_by);

        $auditLog = AuditLog::query()->sole();

        $this->assertSame($supplier->id, $auditLog->user_id);
        $this->assertSame('supplier_proposal.created', $auditLog->action);
        $this->assertSame($supplierProposal->getMorphClass(), $auditLog->auditable_type);
        $this->assertSame($supplierProposal->id, $auditLog->auditable_id);
        $this->assertSame(['sourcing_request_id' => $sourcingRequest->id], $auditLog->metadata);
    }

    #[DataProvider('unauthorizedUsers')]
    public function test_unauthorized_user_cannot_submit_supplier_proposal(string $actor, int $expectedStatus): void
    {
        $sourcingRequest = SourcingRequest::factory()->published()->create();

        if ($actor === 'buyer') {
            Sanctum::actingAs(User::factory()->buyer()->create());
        }

        $this->postJson(
            "/api/sourcing-requests/{$sourcingRequest->id}/proposals",
            [
                'amount' => 12500.50,
                'currency' => 'USD',
            ]
        )->assertStatus($expectedStatus);

        $this->assertDatabaseCount('supplier_proposals', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    #[DataProvider('nonPublishedStatuses')]
    public function test_supplier_cannot_submit_proposal_for_non_published_sourcing_request(
        SourcingRequestStatus $status
    ): void {
        $supplier = User::factory()->supplier()->create();

        $sourcingRequest = SourcingRequest::factory()->create([
            'status' => $status,
            'published_at' => null,
        ]);

        Sanctum::actingAs($supplier);

        $this->postJson(
            "/api/sourcing-requests/{$sourcingRequest->id}/proposals",
            [
                'amount' => 12500.50,
                'currency' => 'USD',
            ]
        )
            ->assertConflict()
            ->assertJsonPath(
                'message',
                'Proposals can only be submitted for published sourcing requests.'
            );

        $this->assertDatabaseCount('supplier_proposals', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_supplier_company_cannot_submit_second_proposal(): void
    {
        $supplier = User::factory()->supplier()->create();

        $otherSupplierUser = User::factory()->create([
            'company_id' => $supplier->company_id,
            'role' => UserRole::Supplier,
        ]);

        $sourcingRequest = SourcingRequest::factory()->published()->create();

        SupplierProposal::factory()->create([
            'sourcing_request_id' => $sourcingRequest->id,
            'company_id' => $supplier->company_id,
            'created_by' => $supplier->id,
        ]);

        Sanctum::actingAs($otherSupplierUser);

        $this->postJson(
            "/api/sourcing-requests/{$sourcingRequest->id}/proposals",
            [
                'amount' => 15000,
                'currency' => 'USD',
            ]
        )
            ->assertConflict()
            ->assertJsonPath(
                'message',
                'Your company has already submitted a proposal for this sourcing request.'
            );

        $this->assertDatabaseCount('supplier_proposals', 1);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public static function nonPublishedStatuses(): array
    {
        return [
            'draft' => [SourcingRequestStatus::Draft],
            'pending moderation' => [SourcingRequestStatus::PendingModeration],
            'rejected' => [SourcingRequestStatus::Rejected],
            'archived' => [SourcingRequestStatus::Archived],
        ];
    }

    public static function unauthorizedUsers(): array
    {
        return [
            'buyer' => ['buyer', 403],
            'guest' => ['guest', 401],
        ];
    }
}
