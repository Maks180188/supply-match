<?php

namespace App\Actions\SupplierProposals;

use App\Enums\SourcingRequestStatus;
use App\Models\AuditLog;
use App\Models\SourcingRequest;
use App\Models\SupplierProposal;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateSupplierProposal
{
    /**
     * @throws Throwable
     */
    public function execute(
        User $user,
        SourcingRequest $sourcingRequest,
        array $data
    ): SupplierProposal {
        return DB::transaction(function () use ($user, $sourcingRequest, $data): SupplierProposal {
            $sourcingRequest = SourcingRequest::query()
                ->lockForUpdate()
                ->findOrFail($sourcingRequest->getKey());

            if ($sourcingRequest->status !== SourcingRequestStatus::Published) {
                throw new DomainException('Proposals can only be submitted for published sourcing requests.');
            }

            $proposalExists = SupplierProposal::query()
                ->where('sourcing_request_id', $sourcingRequest->id)
                ->where('company_id', $user->company_id)
                ->exists();

            if ($proposalExists) {
                throw new DomainException('Your company has already submitted a proposal for this sourcing request.');
            }

            $supplierProposal = SupplierProposal::create([
                'sourcing_request_id' => $sourcingRequest->id,
                'company_id' => $user->company_id,
                'created_by' => $user->id,
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'delivery_days' => $data['delivery_days'] ?? null,
                'message' => $data['message'] ?? null,
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'supplier_proposal.created',
                'auditable_type' => $supplierProposal->getMorphClass(),
                'auditable_id' => $supplierProposal->getKey(),
                'metadata' => [
                    'sourcing_request_id' => $sourcingRequest->id,
                ],
            ]);

            return $supplierProposal;
        });
    }
}
