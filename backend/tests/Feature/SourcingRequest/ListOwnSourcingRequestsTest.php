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

class ListOwnSourcingRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_sees_only_own_company_requests_from_recently_updated(): void
    {
        $buyer = $this->createUser(CompanyType::Buyer, UserRole::Buyer);
        $otherBuyer = $this->createUser(CompanyType::Buyer, UserRole::Buyer);
        $category = $this->createCategory();

        $olderRequest = $this->createSourcingRequest(
            $buyer,
            $category,
            SourcingRequestStatus::Draft,
            'Office laptops'
        );

        $newerRequest = $this->createSourcingRequest(
            $buyer,
            $category,
            SourcingRequestStatus::Rejected,
            'Office monitors',
            'The description needs more details.'
        );

        $olderRequest->forceFill([
            'updated_at' => now()->subHour(),
        ])->saveQuietly();

        $newerRequest->keywords()->create([
            'keyword' => 'monitors',
        ]);

        $otherCompanyRequest = $this->createSourcingRequest(
            $otherBuyer,
            $category,
            SourcingRequestStatus::Published,
            'Office keyboards'
        );

        Sanctum::actingAs($buyer);

        $response = $this->getJson('/api/my/sourcing-requests');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newerRequest->id)
            ->assertJsonPath('data.0.status', SourcingRequestStatus::Rejected->value)
            ->assertJsonPath('data.0.rejection_reason', 'The description needs more details.')
            ->assertJsonPath('data.0.keywords.0', 'monitors')
            ->assertJsonPath('data.1.id', $olderRequest->id)
            ->assertJsonMissing(['id' => $otherCompanyRequest->id]);
    }

    public function test_supplier_cannot_view_own_sourcing_requests(): void
    {
        $supplier = $this->createUser(CompanyType::Supplier, UserRole::Supplier);

        Sanctum::actingAs($supplier);

        $this->getJson('/api/my/sourcing-requests')
            ->assertForbidden();
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
            'name' => 'IT Equipment',
            'slug' => 'it-equipment',
        ]);
    }

    private function createSourcingRequest(
        User $buyer,
        Category $category,
        SourcingRequestStatus $status,
        string $title,
        ?string $rejectionReason = null
    ): SourcingRequest {
        return SourcingRequest::create([
            'company_id' => $buyer->company_id,
            'category_id' => $category->id,
            'created_by' => $buyer->id,
            'title' => $title,
            'description' => 'Equipment for our employees.',
            'status' => $status,
            'rejection_reason' => $rejectionReason,
            'published_at' => $status === SourcingRequestStatus::Published ? now() : null,
        ]);
    }
}
