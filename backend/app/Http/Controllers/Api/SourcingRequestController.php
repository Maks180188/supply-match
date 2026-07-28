<?php

namespace App\Http\Controllers\Api;

use App\Actions\SourcingRequests\CreateSourcingRequest;
use App\Actions\SourcingRequests\SubmitSourcingRequest;
use App\Enums\SourcingRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSourcingRequestRequest;
use App\Http\Resources\SourcingRequestResource;
use App\Models\SourcingRequest;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SourcingRequestController extends Controller
{
    public function index()
    {
        $sourcingRequests = SourcingRequest::query()
            ->where('status', SourcingRequestStatus::Published)
            ->with('keywords')
            ->latest('published_at')
            ->paginate();

        return SourcingRequestResource::collection($sourcingRequests);
    }

    /**
     * @throws Throwable
     */
    public function store(StoreSourcingRequestRequest $request, CreateSourcingRequest $createSourcingRequest): JsonResponse
    {
        Gate::authorize('create', SourcingRequest::class);
        $sourcingRequest = $createSourcingRequest->execute($request->user(), $request->validated());

        return new SourcingRequestResource($sourcingRequest)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function submit(SourcingRequest $sourcingRequest, SubmitSourcingRequest $submitSourcingRequest): JsonResponse
    {
        Gate::authorize('submit', $sourcingRequest);

        try {
            $sourcingRequest = $submitSourcingRequest->execute(request()->user(), $sourcingRequest);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }

        return new SourcingRequestResource($sourcingRequest)->response();
    }
}
