<?php

namespace Database\Factories;

use App\Enums\SourcingRequestStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Company;
use App\Models\SourcingRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SourcingRequest>
 */
class SourcingRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory()->buyer(),
            'category_id' => Category::factory(),
            'created_by' => function (array $attributes): int {
                return User::factory()->create([
                    'company_id' => $attributes['company_id'],
                    'role' => UserRole::Buyer,
                ])->id;
            },
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => SourcingRequestStatus::Draft,
            'submission_deadline' => fake()->optional()->dateTimeBetween('+1 week', '+3 months'),
            'published_at' => null,
            'rejection_reason' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => SourcingRequestStatus::Published,
            'published_at' => now(),
        ]);
    }
}
