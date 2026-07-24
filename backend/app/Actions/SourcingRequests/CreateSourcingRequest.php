<?php

namespace App\Actions\SourcingRequests;

use App\Enums\SourcingRequestStatus;
use App\Models\AuditLog;
use App\Models\SourcingRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateSourcingRequest
{
    /**
     * @throws Throwable
     */
    public function execute(User $user, array $data): SourcingRequest
    {
        return DB::transaction(function () use ($user, $data) {
            $keywords = collect($data['keywords'] ?? [])
                ->map(fn (string $keyword) => mb_strtolower(trim($keyword)))
                ->filter()
                ->unique()
                ->values();

            $sourcingRequest = SourcingRequest::create([
                'company_id' => $user->company_id,
                'category_id' => $data['category_id'],
                'created_by' => $user->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => SourcingRequestStatus::Draft,
                'submission_deadline' => $data['submission_deadline'] ?? null,
            ]);

            $sourcingRequest->keywords()->createMany(
                $keywords->map(fn (string $keyword) => ['keyword' => $keyword])->all()
            );

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'sourcing_request.created',
                'auditable_type' => $sourcingRequest->getMorphClass(),
                'auditable_id' => $sourcingRequest->getKey(),
                'metadata' => [
                    'status' => SourcingRequestStatus::Draft->value,
                ],
            ]);

            return $sourcingRequest->load('keywords');
        });
    }
}
