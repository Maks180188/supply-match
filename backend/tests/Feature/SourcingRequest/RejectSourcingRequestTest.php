<?php

namespace Tests\Feature\SourcingRequest;

use App\Enums\CompanyType;
use App\Enums\SourcingRequestStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Company;
use App\Models\SourcingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RejectSourcingRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reject_pending_moderation_sourcing_request(): void
    {
        $buyerCompany = Company::factory()->create([
            'type' => CompanyType::Buyer,
        ]);

        $buyer = User::factory()->for($buyerCompany)->create([
            'role' => UserRole::Buyer,
        ]);

        $adminCompany = Company::factory()->create([
            'type' => CompanyType::Buyer,
        ]);

        $admin = User::factory()->for($adminCompany)->create([
            'role' => UserRole::Admin,
        ]);

        $category = Category::create([
            'name' => 'IT Equipment',
            'slug' => 'it-equipment',
        ]);

        $sourcingRequest = SourcingRequest::create([
            'company_id' => $buyerCompany->id,
            'category_id' => $category->id,
            'created_by' => $buyer->id,
            'title' => 'Office laptops',
            'description' => 'We need laptops for our employees.',
            'status' => SourcingRequestStatus::PendingModeration,
        ]);

        $reason = 'The request description does not contain enough information.';

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/admin/sourcing-requests/{$sourcingRequest->id}/reject", ['reason' => $reason]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $sourcingRequest->id)
            ->assertJsonPath('data.status', SourcingRequestStatus::Rejected->value)
            ->assertJsonPath('data.rejection_reason', $reason);

        $sourcingRequest->refresh();

        $this->assertSame(SourcingRequestStatus::Rejected, $sourcingRequest->status);
        $this->assertSame($reason, $sourcingRequest->rejection_reason);
    }

    #[DataProvider('invalidStatusesProvider')]
    public function test_sourcing_request_with_invalid_status_cannot_be_rejected(SourcingRequestStatus $status): void
    {
        $buyerCompany = Company::factory()->create([
            'type' => CompanyType::Buyer,
        ]);

        $buyer = User::factory()->for($buyerCompany)->create([
            'role' => UserRole::Buyer,
        ]);

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $category = Category::create([
            'name' => 'IT Equipment',
            'slug' => 'it-equipment',
        ]);

        $sourcingRequest = SourcingRequest::create([
            'company_id' => $buyerCompany->id,
            'category_id' => $category->id,
            'created_by' => $buyer->id,
            'title' => 'Office laptops',
            'description' => 'We need laptops for our employees.',
            'status' => $status,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/admin/sourcing-requests/{$sourcingRequest->id}/reject", ['reason' => 'The request description does not contain enough information.']);

        $response
            ->assertConflict()
            ->assertJsonPath(
                'message',
                'Only pending moderation sourcing requests can be rejected.'
            );

        $sourcingRequest->refresh();

        $this->assertSame($status, $sourcingRequest->status);
        $this->assertNull($sourcingRequest->rejection_reason);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_already_rejected_sourcing_request_cannot_be_rejected_again(): void
    {
        $buyerCompany = Company::factory()->create([
            'type' => CompanyType::Buyer,
        ]);

        $buyer = User::factory()->for($buyerCompany)->create([
            'role' => UserRole::Buyer,
        ]);

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $category = Category::create([
            'name' => 'IT Equipment',
            'slug' => 'it-equipment',
        ]);

        $originalReason = 'The request does not include technical requirements.';

        $sourcingRequest = SourcingRequest::create([
            'company_id' => $buyerCompany->id,
            'category_id' => $category->id,
            'created_by' => $buyer->id,
            'title' => 'Office laptops',
            'description' => 'We need laptops for our employees.',
            'status' => SourcingRequestStatus::Rejected,
            'rejection_reason' => $originalReason,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/admin/sourcing-requests/{$sourcingRequest->id}/reject", ['reason' => 'A different rejection reason.']);

        $response
            ->assertConflict()
            ->assertJsonPath(
                'message',
                'Only pending moderation sourcing requests can be rejected.'
            );

        $sourcingRequest->refresh();

        $this->assertSame(SourcingRequestStatus::Rejected, $sourcingRequest->status);
        $this->assertSame($originalReason, $sourcingRequest->rejection_reason);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_published_sourcing_request_cannot_be_rejected(): void
    {
        $buyerCompany = Company::factory()->create([
            'type' => CompanyType::Buyer,
        ]);

        $buyer = User::factory()->for($buyerCompany)->create([
            'role' => UserRole::Buyer,
        ]);

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $category = Category::create([
            'name' => 'IT Equipment',
            'slug' => 'it-equipment',
        ]);

        $publishedAt = now()->subDay()->startOfSecond();

        $sourcingRequest = SourcingRequest::create([
            'company_id' => $buyerCompany->id,
            'category_id' => $category->id,
            'created_by' => $buyer->id,
            'title' => 'Office laptops',
            'description' => 'We need laptops for our employees.',
            'status' => SourcingRequestStatus::Published,
            'published_at' => $publishedAt,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/admin/sourcing-requests/{$sourcingRequest->id}/reject", ['reason' => 'The request description does not contain enough information.']);

        $response
            ->assertConflict()
            ->assertJsonPath(
                'message',
                'Only pending moderation sourcing requests can be rejected.'
            );

        $sourcingRequest->refresh();

        $this->assertSame(SourcingRequestStatus::Published, $sourcingRequest->status);
        $this->assertTrue($publishedAt->equalTo($sourcingRequest->published_at));
        $this->assertNull($sourcingRequest->rejection_reason);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_rejecting_sourcing_request_writes_audit_log(): void
    {
        $buyerCompany = Company::factory()->create([
            'type' => CompanyType::Buyer,
        ]);

        $buyer = User::factory()->for($buyerCompany)->create([
            'role' => UserRole::Buyer,
        ]);

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $category = Category::create([
            'name' => 'IT Equipment',
            'slug' => 'it-equipment',
        ]);

        $sourcingRequest = SourcingRequest::create([
            'company_id' => $buyerCompany->id,
            'category_id' => $category->id,
            'created_by' => $buyer->id,
            'title' => 'Office laptops',
            'description' => 'We need laptops for our employees.',
            'status' => SourcingRequestStatus::PendingModeration,
        ]);

        $reason = 'The request description does not contain enough information.';

        Sanctum::actingAs($admin);

        $this->postJson("/api/admin/sourcing-requests/{$sourcingRequest->id}/reject", ['reason' => $reason])->assertOk();

        $auditLog = AuditLog::query()
            ->where('action', 'sourcing_request.rejected')
            ->where('auditable_type', $sourcingRequest->getMorphClass())
            ->where('auditable_id', $sourcingRequest->getKey())
            ->sole();

        $this->assertSame($admin->id, $auditLog->user_id);

        $this->assertSame([
            'from_status' => SourcingRequestStatus::PendingModeration->value,
            'to_status' => SourcingRequestStatus::Rejected->value,
            'reason' => $reason,
        ], $auditLog->metadata);
    }

    public static function invalidStatusesProvider(): array
    {
        return [
            'draft' => [SourcingRequestStatus::Draft],
            'archived' => [SourcingRequestStatus::Archived],
        ];
    }
}
