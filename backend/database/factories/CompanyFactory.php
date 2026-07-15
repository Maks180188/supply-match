<?php

namespace Database\Factories;

use App\Enums\CompanyType;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => fake()->randomElement(CompanyType::cases()),
        ];
    }

    public function buyer(): static
    {
        return $this->state(fn (): array => [
            'type' => CompanyType::Buyer,
        ]);
    }

    public function supplier(): static
    {
        return $this->state(fn (): array => [
            'type' => CompanyType::Supplier,
        ]);
    }
}
