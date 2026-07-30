<?php

namespace Tests\Feature\SourcingRequest;

use App\Enums\SourcingRequestStatus;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\SourcingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SubmitSourcingRequestTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('submittableStatuses')]
    public function test_owner_buyer_can_submit_draft_or_rejected_sourcing_request(
        SourcingRequestStatus $status,
        ?string $rejectionReason
    ): void {
        $buyer = User::factory()->buyer()->create();
        $category = $this->createCategory();

        $sourcingRequest = $this->createSourcingRequest(
            $buyer,
            $category,
            $status,
            $rejectionReason
        );

        Sanctum::actingAs($buyer);

        $response = $this->postJson(
            "/api/sourcing-requests/{$sourcingRequest->id}/submit"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                SourcingRequestStatus::PendingModeration->value
            )
            ->assertJsonPath('data.rejection_reason', null);

        $this->assertDatabaseHas('sourcing_requests', [
            'id' => $sourcingRequest->id,
            'status' => SourcingRequestStatus::PendingModeration->value,
            'rejection_reason' => null,
        ]);

        $auditLog = AuditLog::query()->sole();

        $this->assertSame($buyer->id, $auditLog->user_id);
        $this->assertSame('sourcing_request.submitted', $auditLog->action);
        $this->assertSame([
            'from_status' => $status->value,
            'to_status' => SourcingRequestStatus::PendingModeration->value,
        ], $auditLog->metadata);
    }

    #[DataProvider('unauthorizedUsers')]
    public function test_unauthorized_user_cannot_submit_sourcing_request(
        string $actor,
        int $expectedStatus
    ): void {
        $owner = User::factory()->buyer()->create();
        $category = $this->createCategory();
        $sourcingRequest = $this->createSourcingRequest(
            $owner,
            $category,
            SourcingRequestStatus::Draft
        );

        if ($actor === 'other buyer') {
            Sanctum::actingAs(User::factory()->buyer()->create());
        }

        if ($actor === 'supplier') {
            Sanctum::actingAs(User::factory()->supplier()->create());
        }

        $this->postJson("/api/sourcing-requests/{$sourcingRequest->id}/submit")
            ->assertStatus($expectedStatus);

        $this->assertDatabaseHas('sourcing_requests', [
            'id' => $sourcingRequest->id,
            'status' => SourcingRequestStatus::Draft->value,
        ]);

        $this->assertDatabaseCount('audit_logs', 0);
    }

    #[DataProvider('nonSubmittableStatuses')]
    public function test_sourcing_request_in_invalid_status_cannot_be_submitted(
        SourcingRequestStatus $status
    ): void {
        $buyer = User::factory()->buyer()->create();
        $category = $this->createCategory();
        $sourcingRequest = $this->createSourcingRequest($buyer, $category, $status);

        Sanctum::actingAs($buyer);

        $this->postJson("/api/sourcing-requests/{$sourcingRequest->id}/submit")
            ->assertConflict()
            ->assertJsonPath(
                'message',
                'Only draft or rejected sourcing requests can be submitted.'
            );

        $this->assertDatabaseHas('sourcing_requests', [
            'id' => $sourcingRequest->id,
            'status' => $status->value,
        ]);

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public static function submittableStatuses(): array
    {
        return [
            'draft' => [
                SourcingRequestStatus::Draft,
                null,
            ],
            'rejected' => [
                SourcingRequestStatus::Rejected,
                'The description needs more details.',
            ],
        ];
    }

    public static function unauthorizedUsers(): array
    {
        return [
            'another buyer' => ['other buyer', 403],
            'supplier' => ['supplier', 403],
            'guest' => ['guest', 401],
        ];
    }

    public static function nonSubmittableStatuses(): array
    {
        return [
            'pending moderation' => [SourcingRequestStatus::PendingModeration],
            'published' => [SourcingRequestStatus::Published],
            'archived' => [SourcingRequestStatus::Archived],
        ];
    }

    private function createCategory(): Category
    {
        return Category::create([
            'name' => 'IT Equipment',
            'slug' => 'it-equipment',
        ]);
    }

    private function createSourcingRequest(
        User $buyer,
        Category $category,
        SourcingRequestStatus $status,
        ?string $rejectionReason = null
    ): SourcingRequest {
        return SourcingRequest::create([
            'company_id' => $buyer->company_id,
            'category_id' => $category->id,
            'created_by' => $buyer->id,
            'title' => 'Office laptops',
            'description' => 'We need laptops for our employees.',
            'status' => $status,
            'rejection_reason' => $rejectionReason,
            'published_at' => $status === SourcingRequestStatus::Published ? now() : null,
        ]);
    }
}
