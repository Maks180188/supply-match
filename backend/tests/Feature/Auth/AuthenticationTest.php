<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

final class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Origin', 'http://localhost:5173');
    }

    public function test_user_can_register_a_buyer_company(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'company_name' => 'Acme Procurement',
            'company_type' => 'buyer',
            'name' => 'John Buyer',
            'email' => 'john@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'John Buyer')
            ->assertJsonPath('data.email', 'john@example.com')
            ->assertJsonPath('data.role', 'buyer')
            ->assertJsonPath('data.company.name', 'Acme Procurement')
            ->assertJsonPath('data.company.type', 'buyer')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'company' => [
                        'id',
                        'name',
                        'type',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('companies', [
            'name' => 'Acme Procurement',
            'type' => 'buyer',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Buyer',
            'email' => 'john@example.com',
            'role' => 'buyer',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_user_can_register_a_supplier_company(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'company_name' => 'Global Supplies',
            'company_type' => 'supplier',
            'name' => 'Jane Supplier',
            'email' => 'jane@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.role', 'supplier')
            ->assertJsonPath('data.company.type', 'supplier');

        $this->assertDatabaseHas('companies', [
            'name' => 'Global Supplies',
            'type' => 'supplier',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'role' => 'supplier',
        ]);
    }

    public function test_registration_validates_required_fields(): void
    {
        $response = $this->postJson('/api/auth/register', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'company_name',
                'company_type',
                'name',
                'email',
                'password',
            ]);

        $this->assertDatabaseCount('companies', 0);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_email_must_be_unique(): void
    {
        $this->postJson('/api/auth/register', [
            'company_name' => 'First Company',
            'company_type' => 'buyer',
            'name' => 'First User',
            'email' => 'duplicate@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertCreated();

        $response = $this->postJson('/api/auth/register', [
            'company_name' => 'Second Company',
            'company_type' => 'supplier',
            'name' => 'Second User',
            'email' => 'duplicate@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_user_can_log_in(): void
    {
        $company = Company::factory()->buyer()->create([
            'name' => 'Acme Procurement',
        ]);

        User::factory()->for($company)->create([
            'name' => 'John Buyer',
            'email' => 'john@example.com',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.email', 'john@example.com')
            ->assertJsonPath('data.role', 'buyer')
            ->assertJsonPath('data.company.name', 'Acme Procurement');

        $this->assertAuthenticated();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $company = Company::factory()->buyer()->create();

        User::factory()->for($company)->create([
            'email' => 'john@example.com',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'WrongPassword',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath(
                'errors.email.0',
                'The provided credentials are incorrect.',
            );

        $this->assertGuest();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_authenticated_user_can_retrieve_profile(): void
    {
        $company = Company::factory()->buyer()->create([
            'name' => 'Acme Procurement',
        ]);

        $user = User::factory()->for($company)->create([
            'name' => 'John Buyer',
            'email' => 'john@example.com',
        ]);

        $this->actingAs($user)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.name', 'John Buyer')
            ->assertJsonPath('data.email', 'john@example.com')
            ->assertJsonPath('data.role', 'buyer')
            ->assertJsonPath('data.company.name', 'Acme Procurement')
            ->assertJsonPath('data.company.type', 'buyer');
    }

    public function test_guest_cannot_retrieve_profile(): void
    {
        $this->getJson('/api/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $company = Company::factory()->buyer()->create();
        $user = User::factory()->for($company)->create();

        $this->actingAs($user)
            ->postJson('/api/auth/logout')
            ->assertNoContent();

        $this->assertGuest('web');
        $this->assertDatabaseCount('personal_access_tokens', 0);

        Auth::forgetGuards();

        $this->getJson('/api/auth/me');
    }
}
