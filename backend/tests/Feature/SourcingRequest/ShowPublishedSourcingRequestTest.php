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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShowPublishedSourcingRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_published_sourcing_request(): void
    {
        $company = Company::factory()->create([
            'type' => CompanyType::Buyer,
        ]);

        $buyer = User::factory()->for($company)->create([
            'role' => UserRole::Buyer,
        ]);

        $category = Category::create([
            'name' => 'IT Equipment',
            'slug' => 'it-equipment',
        ]);

        $sourcingRequest = SourcingRequest::create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by' => $buyer->id,
            'title' => 'Office laptops',
            'description' => 'We need laptops for our employees.',
            'status' => SourcingRequestStatus::Published,
            'published_at' => now(),
        ]);

        $sourcingRequest->keywords()->createMany([
            ['keyword' => 'laptops'],
            ['keyword' => 'office equipment'],
        ]);

        $response = $this->getJson("/api/sourcing-requests/{$sourcingRequest->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $sourcingRequest->id)
            ->assertJsonPath('data.status', SourcingRequestStatus::Published->value)
            ->assertJsonPath('data.title', 'Office laptops')
            ->assertJsonPath('data.keywords', [
                'laptops',
                'office equipment',
            ]);
    }

    #[DataProvider('nonPublicStatusesProvider')]
    public function test_non_public_sourcing_request_returns_not_found(
        SourcingRequestStatus $status
    ): void {
        $company = Company::factory()->create([
            'type' => CompanyType::Buyer,
        ]);

        $buyer = User::factory()->for($company)->create([
            'role' => UserRole::Buyer,
        ]);

        $category = Category::create([
            'name' => 'IT Equipment',
            'slug' => 'it-equipment',
        ]);

        $sourcingRequest = SourcingRequest::create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by' => $buyer->id,
            'title' => 'Office laptops',
            'description' => 'We need laptops for our employees.',
            'status' => $status,
        ]);

        $this->getJson("/api/sourcing-requests/{$sourcingRequest->id}")->assertNotFound();
    }

    public static function nonPublicStatusesProvider(): array
    {
        return [
            'draft' => [SourcingRequestStatus::Draft],
            'pending moderation' => [SourcingRequestStatus::PendingModeration],
            'rejected' => [SourcingRequestStatus::Rejected],
            'archived' => [SourcingRequestStatus::Archived],
        ];
    }
}
