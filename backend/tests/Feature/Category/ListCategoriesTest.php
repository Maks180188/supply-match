<?php

namespace Tests\Feature\Category;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_categories_sorted_by_name(): void
    {
        $parentCategory = Category::factory()->create([
            'name' => 'Office Supplies',
            'slug' => 'office-supplies',
            'description' => 'Supplies for offices.',
        ]);

        $childCategory = Category::factory()->create([
            'parent_id' => $parentCategory->id,
            'name' => 'IT Equipment',
            'slug' => 'it-equipment',
            'description' => 'Computers and related equipment.',
        ]);

        $response = $this->getJson('/api/categories');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $childCategory->id)
            ->assertJsonPath('data.0.parent_id', $parentCategory->id)
            ->assertJsonPath('data.0.name', 'IT Equipment')
            ->assertJsonPath('data.0.slug', 'it-equipment')
            ->assertJsonPath('data.0.description', 'Computers and related equipment.')
            ->assertJsonPath('data.1.id', $parentCategory->id)
            ->assertJsonPath('data.1.parent_id', null)
            ->assertJsonPath('data.1.name', 'Office Supplies');
    }
}
