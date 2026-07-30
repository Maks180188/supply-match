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

class UpdateSourcingRequestTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('editableStatuses')]
    public function test_buyer_can_update_draft_or_rejected_sourcing_request(
        SourcingRequestStatus $status,
        ?string $rejectionReason
    ): void {
        $buyer = User::factory()->buyer()->create();
        $category = $this->createCategory('IT Equipment', 'it-equipment');
        $newCategory = $this->createCategory('Office Equipment', 'office-equipment');

        $sourcingRequest = $this->createSourcingRequest(
            $buyer,
            $category,
            $status,
            $rejectionReason
        );

        $sourcingRequest->keywords()->create([
            'keyword' => 'old keyword',
        ]);

        Sanctum::actingAs($buyer);

        $response = $this->putJson("/api/my/sourcing-requests/{$sourcingRequest->id}", [
            'category_id' => $newCategory->id,
            'title' => 'Updated office equipment',
            'description' => 'Updated description for the sourcing request.',
            'submission_deadline' => now()->addMonth()->toDateString(),
            'keywords' => [
                'Laptops',
                ' Docking Stations ',
                'laptops',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.category_id', $newCategory->id)
            ->assertJsonPath('data.title', 'Updated office equipment')
            ->assertJsonPath('data.status', $status->value)
            ->assertJsonPath('data.rejection_reason', $rejectionReason);

        $this->assertEqualsCanonicalizing(
            ['laptops', 'docking stations'],
            $response->json('data.keywords')
        );

        $this->assertDatabaseHas('sourcing_requests', [
            'id' => $sourcingRequest->id,
            'category_id' => $newCategory->id,
            'title' => 'Updated office equipment',
            'status' => $status->value,
            'rejection_reason' => $rejectionReason,
        ]);

        $this->assertDatabaseMissing('request_keywords', [
            'sourcing_request_id' => $sourcingRequest->id,
            'keyword' => 'old keyword',
        ]);

        $this->assertDatabaseCount('request_keywords', 2);

        $auditLog = AuditLog::query()->sole();

        $this->assertSame($buyer->id, $auditLog->user_id);
        $this->assertSame('sourcing_request.updated', $auditLog->action);
        $this->assertSame(['status' => $status->value], $auditLog->metadata);
    }

    #[DataProvider('disallowedUpdateCases')]
    public function test_buyer_cannot_update_disallowed_sourcing_request(
        bool $belongsToBuyer,
        SourcingRequestStatus $status,
        int $expectedStatus
    ): void {
        $buyer = User::factory()->buyer()->create();
        $owner = $belongsToBuyer ? $buyer : User::factory()->buyer()->create();
        $category = $this->createCategory('IT Equipment', 'it-equipment');

        $sourcingRequest = $this->createSourcingRequest($owner, $category, $status);

        Sanctum::actingAs($buyer);

        $response = $this->putJson("/api/my/sourcing-requests/{$sourcingRequest->id}", [
            'category_id' => $category->id,
            'title' => 'Updated title',
            'description' => 'Updated description for the sourcing request.',
        ]);

        $response->assertStatus($expectedStatus);

        if ($expectedStatus === 409) {
            $response->assertJsonPath(
                'message',
                'Only draft or rejected sourcing requests can be updated.'
            );
        }

        $this->assertDatabaseHas('sourcing_requests', [
            'id' => $sourcingRequest->id,
            'title' => 'Office laptops',
            'status' => $status->value,
        ]);

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public static function editableStatuses(): array
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

    public static function disallowedUpdateCases(): array
    {
        return [
            'another company draft' => [
                false,
                SourcingRequestStatus::Draft,
                403,
            ],
            'pending moderation' => [
                true,
                SourcingRequestStatus::PendingModeration,
                409,
            ],
            'published' => [
                true,
                SourcingRequestStatus::Published,
                409,
            ],
            'archived' => [
                true,
                SourcingRequestStatus::Archived,
                409,
            ],
        ];
    }

    private function createCategory(string $name, string $slug): Category
    {
        return Category::create([
            'name' => $name,
            'slug' => $slug,
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
