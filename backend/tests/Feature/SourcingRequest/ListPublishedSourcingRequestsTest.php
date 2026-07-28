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
}
