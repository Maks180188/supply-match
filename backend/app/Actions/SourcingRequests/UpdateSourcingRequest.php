<?php

namespace App\Actions\SourcingRequests;

use App\Enums\SourcingRequestStatus;
use App\Models\AuditLog;
use App\Models\SourcingRequest;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpdateSourcingRequest
{
    /**
     * @throws Throwable
     */
    public function execute(User $user, SourcingRequest $sourcingRequest, array $data): SourcingRequest
    {
        return DB::transaction(function () use ($user, $sourcingRequest, $data) {
            $sourcingRequest = SourcingRequest::query()
                ->lockForUpdate()
                ->findOrFail($sourcingRequest->getKey());

            if (! in_array($sourcingRequest->status, [
                SourcingRequestStatus::Draft,
                SourcingRequestStatus::Rejected,
            ], true)) {
                throw new DomainException('Only draft or rejected sourcing requests can be updated.');
            }

            $keywords = collect($data['keywords'] ?? [])
                ->map(fn (string $keyword) => mb_strtolower(trim($keyword)))
                ->filter()
                ->unique()
                ->values();

            $sourcingRequest->update([
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'submission_deadline' => $data['submission_deadline'] ?? null,
            ]);

            $sourcingRequest->keywords()->delete();

            $sourcingRequest->keywords()->createMany(
                $keywords->map(fn (string $keyword) => ['keyword' => $keyword])->all()
            );

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'sourcing_request.updated',
                'auditable_type' => $sourcingRequest->getMorphClass(),
                'auditable_id' => $sourcingRequest->getKey(),
                'metadata' => [
                    'status' => $sourcingRequest->status->value,
                ],
            ]);

            return $sourcingRequest->load('keywords');
        });
    }
}
