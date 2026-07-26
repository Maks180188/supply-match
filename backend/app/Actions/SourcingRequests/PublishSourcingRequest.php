<?php

namespace App\Actions\SourcingRequests;

use App\Enums\SourcingRequestStatus;
use App\Models\AuditLog;
use App\Models\SourcingRequest;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Throwable;

class PublishSourcingRequest
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

            if ($sourcingRequest->status !== SourcingRequestStatus::PendingModeration) {
                throw new DomainException('Only pending moderation sourcing requests can be published.');
            }

            $sourcingRequest->status = SourcingRequestStatus::Published;
            $sourcingRequest->published_at = now();
            $sourcingRequest->save();

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'sourcing_request.published',
                'auditable_type' => $sourcingRequest->getMorphClass(),
                'auditable_id' => $sourcingRequest->getKey(),
                'metadata' => [
                    'from_status' => SourcingRequestStatus::PendingModeration->value,
                    'to_status' => SourcingRequestStatus::Published->value,
                ],
            ]);

            return $sourcingRequest->load('keywords');
        });
    }
}
