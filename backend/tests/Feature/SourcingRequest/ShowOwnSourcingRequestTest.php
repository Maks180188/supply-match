<?php

namespace Tests\Feature\SourcingRequest;

use App\Enums\SourcingRequestStatus;
use App\Models\Category;
use App\Models\SourcingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShowOwnSourcingRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_view_own_company_sourcing_request(): void
    {
        $buyer = User::factory()->buyer()->create();
        $category = $this->createCategory();

        $sourcingRequest = $this->createSourcingRequest(
            $buyer,
            $category,
            SourcingRequestStatus::Rejected,
            'The description needs more details.'
        );

        $sourcingRequest->keywords()->create([
            'keyword' => 'laptops',
        ]);

        Sanctum::actingAs($buyer);

        $this->getJson("/api/my/sourcing-requests/{$sourcingRequest->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $sourcingRequest->id)
            ->assertJsonPath('data.status', SourcingRequestStatus::Rejected->value)
            ->assertJsonPath('data.rejection_reason', 'The description needs more details.')
            ->assertJsonPath('data.keywords.0', 'laptops');
    }

    public function test_buyer_cannot_view_another_company_sourcing_request(): void
    {
        $buyer = User::factory()->buyer()->create();
        $otherBuyer = User::factory()->buyer()->create();
        $category = $this->createCategory();

        $sourcingRequest = $this->createSourcingRequest(
            $otherBuyer,
            $category,
            SourcingRequestStatus::Draft
        );

        Sanctum::actingAs($buyer);

        $this->getJson("/api/my/sourcing-requests/{$sourcingRequest->id}")
            ->assertForbidden();
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
        ]);
    }
}
