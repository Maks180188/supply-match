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

class PublishSourcingRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_pending_moderation_sourcing_request(): void
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

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/admin/sourcing-requests/{$sourcingRequest->id}/publish");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $sourcingRequest->id)
            ->assertJsonPath('data.status', SourcingRequestStatus::Published->value);

        $this->assertNotNull($response->json('data.published_at'));

        $sourcingRequest->refresh();

        $this->assertSame(SourcingRequestStatus::Published, $sourcingRequest->status);
        $this->assertNotNull($sourcingRequest->published_at);
    }

    public function test_publishing_sourcing_request_writes_audit_log(): void
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

        Sanctum::actingAs($admin);

        $this->postJson("/api/admin/sourcing-requests/{$sourcingRequest->id}/publish")->assertOk();

        $auditLog = AuditLog::query()
            ->where('action', 'sourcing_request.published')
            ->where('auditable_type', $sourcingRequest->getMorphClass())
            ->where('auditable_id', $sourcingRequest->id)
            ->sole();

        $this->assertSame($admin->id, $auditLog->user_id);
        $this->assertSame([
            'from_status' => SourcingRequestStatus::PendingModeration->value,
            'to_status' => SourcingRequestStatus::Published->value,
        ], $auditLog->metadata);
    }

    public function test_buyer_cannot_publish_sourcing_request(): void
    {
        $buyerCompany = Company::factory()->create([
            'type' => CompanyType::Buyer,
        ]);

        $buyer = User::factory()->for($buyerCompany)->create([
            'role' => UserRole::Buyer,
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

        Sanctum::actingAs($buyer);

        $this->postJson("/api/admin/sourcing-requests/{$sourcingRequest->id}/publish")->assertForbidden();

        $sourcingRequest->refresh();

        $this->assertSame(
            SourcingRequestStatus::PendingModeration,
            $sourcingRequest->status
        );
        $this->assertNull($sourcingRequest->published_at);

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'sourcing_request.published',
            'auditable_type' => $sourcingRequest->getMorphClass(),
            'auditable_id' => $sourcingRequest->id,
        ]);
    }

    public function test_supplier_cannot_publish_sourcing_request(): void
    {
        $buyerCompany = Company::factory()->create([
            'type' => CompanyType::Buyer,
        ]);

        $buyer = User::factory()->for($buyerCompany)->create([
            'role' => UserRole::Buyer,
        ]);

        $supplierCompany = Company::factory()->create([
            'type' => CompanyType::Supplier,
        ]);

        $supplier = User::factory()->for($supplierCompany)->create([
            'role' => UserRole::Supplier,
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

        Sanctum::actingAs($supplier);

        $this->postJson("/api/admin/sourcing-requests/{$sourcingRequest->id}/publish")->assertForbidden();

        $sourcingRequest->refresh();

        $this->assertSame(SourcingRequestStatus::PendingModeration, $sourcingRequest->status);
        $this->assertNull($sourcingRequest->published_at);

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'sourcing_request.published',
            'auditable_type' => $sourcingRequest->getMorphClass(),
            'auditable_id' => $sourcingRequest->id,
        ]);
    }

    #[DataProvider('invalidStatusesProvider')]
    public function test_sourcing_request_with_invalid_status_cannot_be_published(SourcingRequestStatus $status): void
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
            'status' => $status,
        ]);

        Sanctum::actingAs($admin);

        $this->postJson("/api/admin/sourcing-requests/{$sourcingRequest->id}/publish")
            ->assertConflict()
            ->assertJsonPath(
                'message',
                'Only pending moderation sourcing requests can be published.'
            );

        $sourcingRequest->refresh();

        $this->assertSame($status, $sourcingRequest->status);
        $this->assertNull($sourcingRequest->published_at);

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'sourcing_request.published',
            'auditable_type' => $sourcingRequest->getMorphClass(),
            'auditable_id' => $sourcingRequest->id,
        ]);
    }

    public function test_guest_cannot_publish_sourcing_request(): void
    {
        $buyerCompany = Company::factory()->create([
            'type' => CompanyType::Buyer,
        ]);

        $buyer = User::factory()->for($buyerCompany)->create([
            'role' => UserRole::Buyer,
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

        $this->postJson("/api/admin/sourcing-requests/{$sourcingRequest->id}/publish")->assertUnauthorized();

        $sourcingRequest->refresh();

        $this->assertSame(SourcingRequestStatus::PendingModeration, $sourcingRequest->status);
        $this->assertNull($sourcingRequest->published_at);

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'sourcing_request.published',
            'auditable_type' => $sourcingRequest->getMorphClass(),
            'auditable_id' => $sourcingRequest->id,
        ]);
    }

    public function test_already_published_sourcing_request_cannot_be_published_again(): void
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

        $this->postJson("/api/admin/sourcing-requests/{$sourcingRequest->id}/publish")
            ->assertConflict()
            ->assertJsonPath(
                'message',
                'Only pending moderation sourcing requests can be published.'
            );

        $sourcingRequest->refresh();

        $this->assertSame(SourcingRequestStatus::Published, $sourcingRequest->status);
        $this->assertTrue($sourcingRequest->published_at->equalTo($publishedAt));

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'sourcing_request.published',
            'auditable_type' => $sourcingRequest->getMorphClass(),
            'auditable_id' => $sourcingRequest->id,
        ]);
    }

    public function test_admin_cannot_publish_nonexistent_sourcing_request(): void
    {
        $adminCompany = Company::factory()->create([
            'type' => CompanyType::Buyer,
        ]);

        $admin = User::factory()->for($adminCompany)->create([
            'role' => UserRole::Admin,
        ]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/sourcing-requests/999999/publish')->assertNotFound();

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'sourcing_request.published',
        ]);
    }

    public static function invalidStatusesProvider(): array
    {
        return [
            'draft' => [SourcingRequestStatus::Draft],
            'rejected' => [SourcingRequestStatus::Rejected],
            'archived' => [SourcingRequestStatus::Archived],
        ];
    }
}
