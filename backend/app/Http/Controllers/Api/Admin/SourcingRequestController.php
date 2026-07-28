<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\SourcingRequests\PublishSourcingRequest;
use App\Actions\SourcingRequests\RejectSourcingRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectSourcingRequestRequest;
use App\Http\Resources\SourcingRequestResource;
use App\Models\SourcingRequest;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Throwable;

class SourcingRequestController extends Controller
{
    public function publish(SourcingRequest $sourcingRequest, PublishSourcingRequest $publishSourcingRequest): JsonResponse
    {
        Gate::authorize('publish', $sourcingRequest);

        try {
            $sourcingRequest = $publishSourcingRequest->execute(request()->user(), $sourcingRequest);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }

        return new SourcingRequestResource($sourcingRequest)->response();
    }

    public function reject(
        RejectSourcingRequestRequest $request,
        SourcingRequest $sourcingRequest,
        RejectSourcingRequest $rejectSourcingRequest
    ) {
        Gate::authorize('reject', $sourcingRequest);

        try {
            $sourcingRequest = $rejectSourcingRequest->execute(
                $request->user(),
                $sourcingRequest,
                $request->validated('reason')
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }

        return new SourcingRequestResource($sourcingRequest)->response();
    }
}
