<?php

namespace App\Actions\SourcingRequests;

use App\Enums\SourcingRequestStatus;
use App\Models\AuditLog;
use App\Models\SourcingRequest;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Throwable;

class RejectSourcingRequest
{
    /**
     * @throws Throwable
     */
    public function execute(
        User $user,
        SourcingRequest $sourcingRequest,
        string $reason
    ): SourcingRequest {
        return DB::transaction(function () use ($user, $sourcingRequest, $reason) {
            $sourcingRequest = SourcingRequest::query()
                ->lockForUpdate()
                ->findOrFail($sourcingRequest->getKey());

            if ($sourcingRequest->status !== SourcingRequestStatus::PendingModeration) {
                throw new DomainException('Only pending moderation sourcing requests can be rejected.');
            }

            $sourcingRequest->status = SourcingRequestStatus::Rejected;
            $sourcingRequest->rejection_reason = $reason;
            $sourcingRequest->save();

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'sourcing_request.rejected',
                'auditable_type' => $sourcingRequest->getMorphClass(),
                'auditable_id' => $sourcingRequest->getKey(),
                'metadata' => [
                    'from_status' => SourcingRequestStatus::PendingModeration->value,
                    'to_status' => SourcingRequestStatus::Rejected->value,
                    'reason' => $reason,
                ],
            ]);

            return $sourcingRequest->load('keywords');
        });
    }
}
