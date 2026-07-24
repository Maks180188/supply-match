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
use Tests\TestCase;

class CreateSourcingRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_create_draft_sourcing_request(): void
    {
        $user = $this->createUser(CompanyType::Buyer, UserRole::Buyer);
        $category = $this->createCategory();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/sourcing-requests', [
            'category_id' => $category->id,
            'title' => 'Supply of office laptops',
            'description' => 'We need laptops for our new office.',
            'submission_deadline' => now()->addMonth()->toDateString(),
            'keywords' => [
                'Laptops',
                ' Docking Stations ',
                'laptops',
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.company_id', $user->company_id)
            ->assertJsonPath('data.category_id', $category->id)
            ->assertJsonPath('data.created_by', $user->id)
            ->assertJsonPath('data.title', 'Supply of office laptops')
            ->assertJsonPath('data.status', SourcingRequestStatus::Draft->value);

        $this->assertEqualsCanonicalizing(
            [
                'laptops',
                'docking stations',
            ],
            $response->json('data.keywords')
        );

        $this->assertDatabaseHas('sourcing_requests', [
            'company_id' => $user->company_id,
            'category_id' => $category->id,
            'created_by' => $user->id,
            'title' => 'Supply of office laptops',
            'status' => SourcingRequestStatus::Draft->value,
        ]);

        $this->assertDatabaseHas('request_keywords', [
            'keyword' => 'laptops',
        ]);

        $this->assertDatabaseHas('request_keywords', [
            'keyword' => 'docking stations',
        ]);

        $this->assertDatabaseCount('request_keywords', 2);
    }

    public function test_creating_sourcing_request_writes_audit_log(): void
    {
        $user = $this->createUser(CompanyType::Buyer, UserRole::Buyer);
        $category = $this->createCategory();

        Sanctum::actingAs($user);

        $this->postJson('/api/sourcing-requests', [
            'category_id' => $category->id,
            'title' => 'Supply of office laptops',
            'description' => 'We need laptops for our new office.',
        ])->assertCreated();

        $sourcingRequest = SourcingRequest::query()->sole();
        $auditLog = AuditLog::query()->sole();

        $this->assertSame($user->id, $auditLog->user_id);
        $this->assertSame('sourcing_request.created', $auditLog->action);
        $this->assertSame($sourcingRequest->getMorphClass(), $auditLog->auditable_type);
        $this->assertSame($sourcingRequest->getKey(), $auditLog->auditable_id);
        $this->assertSame(['status' => SourcingRequestStatus::Draft->value], $auditLog->metadata);
    }

    public function test_supplier_cannot_create_sourcing_request(): void
    {
        $user = $this->createUser(CompanyType::Supplier, UserRole::Supplier);
        $category = $this->createCategory();

        Sanctum::actingAs($user);

        $this->postJson('/api/sourcing-requests', [
            'category_id' => $category->id,
            'title' => 'Supply of office laptops',
            'description' => 'We need laptops for our new office.',
        ])->assertForbidden();

        $this->assertDatabaseCount('sourcing_requests', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_guest_cannot_create_sourcing_request(): void
    {
        $category = $this->createCategory();

        $this->postJson('/api/sourcing-requests', [
            'category_id' => $category->id,
            'title' => 'Supply of office laptops',
            'description' => 'We need laptops for our new office.',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('sourcing_requests', 0);
    }

    public function test_sourcing_request_data_is_validated(): void
    {
        $user = $this->createUser(CompanyType::Buyer, UserRole::Buyer);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/sourcing-requests', [
            'category_id' => 999999,
            'title' => '',
            'description' => '',
            'submission_deadline' => now()->subDay()->toDateString(),
            'keywords' => array_fill(0, 11, 'keyword'),
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'category_id',
                'title',
                'description',
                'submission_deadline',
                'keywords',
            ]);

        $this->assertDatabaseCount('sourcing_requests', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    private function createUser(CompanyType $companyType, UserRole $role): User
    {
        $company = Company::factory()->create([
            'type' => $companyType,
        ]);

        return User::factory()->create([
            'company_id' => $company->id,
            'role' => $role,
        ]);
    }

    private function createCategory(): Category
    {
        return Category::create([
            'name' => 'Computers',
            'slug' => 'computers',
            'description' => 'Computers and related equipment.',
        ]);
    }
}
