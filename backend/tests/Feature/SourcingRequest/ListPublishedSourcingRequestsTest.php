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

class ListPublishedSourcingRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_only_published_sourcing_requests_from_newest_to_oldest(): void
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

        $olderRequest = SourcingRequest::create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by' => $buyer->id,
            'title' => 'Office laptops',
            'description' => 'We need laptops for our employees.',
            'status' => SourcingRequestStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $newerRequest = SourcingRequest::create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by' => $buyer->id,
            'title' => 'Office monitors',
            'description' => 'We need monitors for our employees.',
            'status' => SourcingRequestStatus::Published,
            'published_at' => now(),
        ]);

        $draftRequest = SourcingRequest::create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by' => $buyer->id,
            'title' => 'Office keyboards',
            'description' => 'We need keyboards for our employees.',
            'status' => SourcingRequestStatus::Draft,
        ]);

        $response = $this->getJson('/api/sourcing-requests');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newerRequest->id)
            ->assertJsonPath('data.0.company.id', $company->id)
            ->assertJsonPath('data.0.category.id', $category->id)
            ->assertJsonPath('data.1.id', $olderRequest->id)
            ->assertJsonMissing(['id' => $draftRequest->id]);
    }

    public function test_published_sourcing_requests_are_paginated(): void
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

        foreach (range(1, 16) as $number) {
            SourcingRequest::create([
                'company_id' => $company->id,
                'category_id' => $category->id,
                'created_by' => $buyer->id,
                'title' => "Office equipment {$number}",
                'description' => 'Equipment for our employees.',
                'status' => SourcingRequestStatus::Published,
                'published_at' => now()->subMinutes($number),
            ]);
        }

        $response = $this->getJson('/api/sourcing-requests');

        $response
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 16);
    }

    public function test_published_sourcing_requests_can_be_filtered_by_category_and_search(): void
    {
        $company = Company::factory()->buyer()->create();
        $buyer = User::factory()->for($company)->create([
            'role' => UserRole::Buyer,
        ]);

        $itCategory = Category::factory()->create();
        $officeCategory = Category::factory()->create();

        $matchingRequest = SourcingRequest::create([
            'company_id' => $company->id,
            'category_id' => $itCategory->id,
            'created_by' => $buyer->id,
            'title' => 'Office laptops',
            'description' => 'Equipment for our employees.',
            'status' => SourcingRequestStatus::Published,
            'published_at' => now(),
        ]);

        $sameCategoryNonMatchingRequest = SourcingRequest::create([
            'company_id' => $company->id,
            'category_id' => $itCategory->id,
            'created_by' => $buyer->id,
            'title' => 'Office monitors',
            'description' => 'Displays for our employees.',
            'status' => SourcingRequestStatus::Published,
            'published_at' => now(),
        ]);

        $otherCategoryRequest = SourcingRequest::create([
            'company_id' => $company->id,
            'category_id' => $officeCategory->id,
            'created_by' => $buyer->id,
            'title' => 'Warehouse laptops',
            'description' => 'Equipment for warehouse employees.',
            'status' => SourcingRequestStatus::Published,
            'published_at' => now(),
        ]);

        $draftRequest = SourcingRequest::create([
            'company_id' => $company->id,
            'category_id' => $itCategory->id,
            'created_by' => $buyer->id,
            'title' => 'Gaming laptops',
            'description' => 'High-performance devices.',
            'status' => SourcingRequestStatus::Draft,
        ]);

        $response = $this->getJson(
            "/api/sourcing-requests?category_id={$itCategory->id}&q=laptop"
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingRequest->id)
            ->assertJsonMissing(['id' => $sameCategoryNonMatchingRequest->id])
            ->assertJsonMissing(['id' => $otherCategoryRequest->id])
            ->assertJsonMissing(['id' => $draftRequest->id]);
    }

    public function test_published_sourcing_requests_can_be_searched_by_title_or_description(): void
    {
        $company = Company::factory()->buyer()->create();
        $buyer = User::factory()->for($company)->create([
            'role' => UserRole::Buyer,
        ]);
        $category = Category::factory()->create();

        $titleMatch = SourcingRequest::create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by' => $buyer->id,
            'title' => 'Industrial Laptops',
            'description' => 'Equipment for warehouse employees.',
            'status' => SourcingRequestStatus::Published,
            'published_at' => now(),
        ]);

        $descriptionMatch = SourcingRequest::create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by' => $buyer->id,
            'title' => 'Office equipment',
            'description' => 'We need durable laptops for field work.',
            'status' => SourcingRequestStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        $unrelatedRequest = SourcingRequest::create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by' => $buyer->id,
            'title' => 'Office chairs',
            'description' => 'Ergonomic furniture for employees.',
            'status' => SourcingRequestStatus::Published,
            'published_at' => now(),
        ]);

        $draftRequest = SourcingRequest::create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by' => $buyer->id,
            'title' => 'Gaming laptops',
            'description' => 'High-performance devices.',
            'status' => SourcingRequestStatus::Draft,
        ]);

        $response = $this->getJson('/api/sourcing-requests?q=LAPTOP');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $titleMatch->id)
            ->assertJsonPath('data.1.id', $descriptionMatch->id)
            ->assertJsonMissing(['id' => $unrelatedRequest->id])
            ->assertJsonMissing(['id' => $draftRequest->id]);
    }

    #[DataProvider('invalidCategoryIds')]
    public function test_category_filter_must_be_valid(mixed $categoryId): void
    {
        $response = $this->getJson("/api/sourcing-requests?category_id={$categoryId}");

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
    }

    public static function invalidCategoryIds(): array
    {
        return [
            'not an integer' => ['invalid'],
            'category does not exist' => [999999],
        ];
    }
}
