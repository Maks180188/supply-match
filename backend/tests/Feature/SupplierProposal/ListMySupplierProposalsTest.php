<?php

namespace Tests\Feature\SupplierProposal;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\SourcingRequest;
use App\Models\SupplierProposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ListMySupplierProposalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_can_view_proposals_of_own_company(): void
    {
        $supplierCompany = Company::factory()->supplier()->create();

        $supplier = User::factory()->create([
            'company_id' => $supplierCompany->id,
            'role' => UserRole::Supplier,
        ]);

        $olderSourcingRequest = SourcingRequest::factory()->published()->create();
        $newerSourcingRequest = SourcingRequest::factory()->published()->create();

        $olderProposal = SupplierProposal::factory()->create([
            'sourcing_request_id' => $olderSourcingRequest->id,
            'company_id' => $supplierCompany->id,
            'created_by' => $supplier->id,
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        $newerProposal = SupplierProposal::factory()->create([
            'sourcing_request_id' => $newerSourcingRequest->id,
            'company_id' => $supplierCompany->id,
            'created_by' => $supplier->id,
        ]);

        SupplierProposal::factory()->create();

        $response = $this
            ->actingAs($supplier)
            ->getJson('/api/my/proposals');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newerProposal->id)
            ->assertJsonPath(
                'data.0.sourcing_request.id',
                $newerSourcingRequest->id
            )
            ->assertJsonPath('data.1.id', $olderProposal->id)
            ->assertJsonPath(
                'data.1.sourcing_request.id',
                $olderSourcingRequest->id
            )
            ->assertJsonPath('meta.total', 2)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    #[DataProvider('unauthorizedUsersProvider')]
    public function test_unauthorized_users_cannot_view_supplier_proposals(?UserRole $role, int $expectedStatus): void
    {
        if ($role !== null) {
            $user = match ($role) {
                UserRole::Buyer => User::factory()->buyer()->create(),
                UserRole::Admin => User::factory()->admin()->create(),
                default => throw new LogicException('Unsupported user role.'),
            };

            $this->actingAs($user);
        }

        $this->getJson('/api/my/proposals')
            ->assertStatus($expectedStatus);
    }

    public static function unauthorizedUsersProvider(): array
    {
        return [
            'buyer' => [UserRole::Buyer, 403],
            'admin' => [UserRole::Admin, 403],
            'guest' => [null, 401],
        ];
    }
}
