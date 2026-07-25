<?php

namespace Tests\Feature\SourcingRequest;

use App\Enums\CompanyType;
use App\Enums\SourcingRequestStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Company;
use App\Models\SourcingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubmitSourcingRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_buyer_can_submit_draft_sourcing_request(): void
    {
        $company = Company::factory()->create([
            'type' => CompanyType::Buyer,
        ]);

        $user = User::factory()->for($company)->create([
            'role' => UserRole::Buyer,
        ]);

        $category = Category::create([
            'name' => 'IT Equipment',
            'slug' => 'it-equipment',
        ]);

        $sourcingRequest = SourcingRequest::create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by' => $user->id,
            'title' => 'Office laptops',
            'description' => 'We need laptops for our employees.',
            'status' => SourcingRequestStatus::Draft,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson(
            "/api/sourcing-requests/{$sourcingRequest->id}/submit"
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $sourcingRequest->id)
            ->assertJsonPath(
                'data.status',
                SourcingRequestStatus::PendingModeration->value
            );

        $this->assertDatabaseHas('sourcing_requests', [
            'id' => $sourcingRequest->id,
            'status' => SourcingRequestStatus::PendingModeration->value,
        ]);
    }

    public function test_submitting_sourcing_request_writes_audit_log(): void
    {
        $company = Company::factory()->create([
            'type' => CompanyType::Buyer,
        ]);

        $user = User::factory()->for($company)->create([
            'role' => UserRole::Buyer,
        ]);

        $category = Category::create([
            'name' => 'IT Equipment',
            'slug' => 'it-equipment',
        ]);

        $sourcingRequest = SourcingRequest::create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by' => $user->id,
            'title' => 'Office laptops',
            'description' => 'We need laptops for our employees.',
            'status' => SourcingRequestStatus::Draft,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/sourcing-requests/{$sourcingRequest->id}/submit")
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'sourcing_request.submitted',
            'auditable_type' => $sourcingRequest->getMorphClass(),
            'auditable_id' => $sourcingRequest->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'metadata' => json_encode([
                'from_status' => SourcingRequestStatus::Draft->value,
                'to_status' => SourcingRequestStatus::PendingModeration->value,
            ]),
        ]);
    }

    public function test_buyer_from_another_company_cannot_submit_sourcing_request(): void
    {
        $ownerCompany = Company::factory()->create([
            'type' => CompanyType::Buyer,
        ]);

        $owner = User::factory()->for($ownerCompany)->create([
            'role' => UserRole::Buyer,
        ]);

        $otherCompany = Company::factory()->create([
            'type' => CompanyType::Buyer,
        ]);

        $otherBuyer = User::factory()->for($otherCompany)->create([
            'role' => UserRole::Buyer,
        ]);

        $category = Category::create([
            'name' => 'IT Equipment',
            'slug' => 'it-equipment',
        ]);

        $sourcingRequest = SourcingRequest::create([
            'company_id' => $ownerCompany->id,
            'category_id' => $category->id,
            'created_by' => $owner->id,
            'title' => 'Office laptops',
            'description' => 'We need laptops for our employees.',
            'status' => SourcingRequestStatus::Draft,
        ]);

        Sanctum::actingAs($otherBuyer);

        $this->postJson("/api/sourcing-requests/{$sourcingRequest->id}/submit")
            ->assertForbidden();

        $this->assertDatabaseHas('sourcing_requests', [
            'id' => $sourcingRequest->id,
            'status' => SourcingRequestStatus::Draft->value,
        ]);

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'sourcing_request.submitted',
            'auditable_type' => $sourcingRequest->getMorphClass(),
            'auditable_id' => $sourcingRequest->id,
        ]);
    }

    public function test_supplier_cannot_submit_sourcing_request(): void
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
            'status' => SourcingRequestStatus::Draft,
        ]);

        Sanctum::actingAs($supplier);

        $this->postJson("/api/sourcing-requests/{$sourcingRequest->id}/submit")
            ->assertForbidden();

        $this->assertDatabaseHas('sourcing_requests', [
            'id' => $sourcingRequest->id,
            'status' => SourcingRequestStatus::Draft->value,
        ]);

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'sourcing_request.submitted',
            'auditable_type' => $sourcingRequest->getMorphClass(),
            'auditable_id' => $sourcingRequest->id,
        ]);
    }

    public function test_guest_cannot_submit_sourcing_request(): void
    {
        $company = Company::factory()->create([
            'type' => CompanyType::Buyer,
        ]);

        $user = User::factory()->for($company)->create([
            'role' => UserRole::Buyer,
        ]);

        $category = Category::create([
            'name' => 'IT Equipment',
            'slug' => 'it-equipment',
        ]);

        $sourcingRequest = SourcingRequest::create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by' => $user->id,
            'title' => 'Office laptops',
            'description' => 'We need laptops for our employees.',
            'status' => SourcingRequestStatus::Draft,
        ]);

        $this->postJson("/api/sourcing-requests/{$sourcingRequest->id}/submit")
            ->assertUnauthorized();

        $this->assertDatabaseHas('sourcing_requests', [
            'id' => $sourcingRequest->id,
            'status' => SourcingRequestStatus::Draft->value,
        ]);

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'sourcing_request.submitted',
            'auditable_type' => $sourcingRequest->getMorphClass(),
            'auditable_id' => $sourcingRequest->id,
        ]);
    }

    public function test_already_submitted_sourcing_request_cannot_be_submitted_again(): void
    {
        $company = Company::factory()->create([
            'type' => CompanyType::Buyer,
        ]);

        $user = User::factory()->for($company)->create([
            'role' => UserRole::Buyer,
        ]);

        $category = Category::create([
            'name' => 'IT Equipment',
            'slug' => 'it-equipment',
        ]);

        $sourcingRequest = SourcingRequest::create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by' => $user->id,
            'title' => 'Office laptops',
            'description' => 'We need laptops for our employees.',
            'status' => SourcingRequestStatus::PendingModeration,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/sourcing-requests/{$sourcingRequest->id}/submit")
            ->assertConflict()
            ->assertJsonPath(
                'message',
                'Only draft sourcing requests can be submitted.'
            );

        $this->assertDatabaseHas('sourcing_requests', [
            'id' => $sourcingRequest->id,
            'status' => SourcingRequestStatus::PendingModeration->value,
        ]);

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'sourcing_request.submitted',
            'auditable_type' => $sourcingRequest->getMorphClass(),
            'auditable_id' => $sourcingRequest->id,
        ]);
    }
}
