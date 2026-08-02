<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\SourcingRequest;
use App\Models\SupplierProposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierProposal>
 */
class SupplierProposalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sourcing_request_id' => SourcingRequest::factory()->published(),
            'company_id' => Company::factory()->supplier(),
            'created_by' => function (array $attributes): int {
                return User::factory()->create([
                    'company_id' => $attributes['company_id'],
                    'role' => UserRole::Supplier,
                ])->id;
            },
            'amount' => fake()->randomFloat(2, 100, 100000),
            'currency' => fake()->randomElement(['USD', 'EUR', 'RUB']),
            'delivery_days' => fake()->optional()->numberBetween(1, 90),
            'message' => fake()->optional()->paragraph(),
        ];
    }
}
