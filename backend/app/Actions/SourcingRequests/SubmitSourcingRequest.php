<?php

namespace App\Actions\SourcingRequests;

use App\Enums\SourcingRequestStatus;
use App\Models\AuditLog;
use App\Models\SourcingRequest;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Throwable;

class SubmitSourcingRequest
{
    /**
     * @throws Throwable
     */
    public function execute(User $user, SourcingRequest $sourcingRequest): SourcingRequest
    {
        return DB::transaction(function () use ($user, $sourcingRequest): SourcingRequest {
            $sourcingRequest = SourcingRequest::query()
                ->lockForUpdate()
                ->findOrFail($sourcingRequest->getKey());

            if (! in_array($sourcingRequest->status, [
                SourcingRequestStatus::Draft,
                SourcingRequestStatus::Rejected,
            ], true)) {
                throw new DomainException(
                    'Only draft or rejected sourcing requests can be submitted.'
                );
            }

            $fromStatus = $sourcingRequest->status;

            $sourcingRequest->status = SourcingRequestStatus::PendingModeration;
            $sourcingRequest->rejection_reason = null;
            $sourcingRequest->save();

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'sourcing_request.submitted',
                'auditable_type' => $sourcingRequest->getMorphClass(),
                'auditable_id' => $sourcingRequest->getKey(),
                'metadata' => [
                    'from_status' => $fromStatus->value,
                    'to_status' => SourcingRequestStatus::PendingModeration->value,
                ],
            ]);

            return $sourcingRequest->load('keywords');
        });
    }
}
