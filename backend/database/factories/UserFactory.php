<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory()->buyer(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Buyer,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (): array => [
            'email_verified_at' => null,
        ]);
    }

    public function buyer(): static
    {
        return $this->state(fn (): array => [
            'company_id' => Company::factory()->buyer(),
            'role' => UserRole::Buyer,
        ]);
    }

    public function supplier(): static
    {
        return $this->state(fn (): array => [
            'company_id' => Company::factory()->supplier(),
            'role' => UserRole::Supplier,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (): array => [
            'company_id' => null,
            'role' => UserRole::Admin,
        ]);
    }
}
