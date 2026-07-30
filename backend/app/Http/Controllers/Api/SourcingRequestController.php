<?php

namespace App\Http\Controllers\Api;

use App\Actions\SourcingRequests\CreateSourcingRequest;
use App\Actions\SourcingRequests\SubmitSourcingRequest;
use App\Actions\SourcingRequests\UpdateSourcingRequest;
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

    public function show(int $sourcingRequest): SourcingRequestResource
    {
        $sourcingRequest = SourcingRequest::query()
            ->whereKey($sourcingRequest)
            ->where('status', SourcingRequestStatus::Published)
            ->with('keywords')
            ->firstOrFail();

        return new SourcingRequestResource($sourcingRequest);
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

    public function mine()
    {
        Gate::authorize('viewOwn', SourcingRequest::class);

        $sourcingRequests = SourcingRequest::query()
            ->where('company_id', request()->user()->company_id)
            ->with('keywords')
            ->latest('updated_at')
            ->paginate();

        return SourcingRequestResource::collection($sourcingRequests);
    }

    public function showMine(SourcingRequest $sourcingRequest): SourcingRequestResource
    {
        Gate::authorize('view', $sourcingRequest);

        return new SourcingRequestResource($sourcingRequest->load('keywords'));
    }

    public function update(
        StoreSourcingRequestRequest $request,
        SourcingRequest $sourcingRequest,
        UpdateSourcingRequest $updateSourcingRequest
    ): JsonResponse {
        Gate::authorize('update', $sourcingRequest);

        try {
            $sourcingRequest = $updateSourcingRequest->execute(
                $request->user(),
                $sourcingRequest,
                $request->validated()
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }

        return new SourcingRequestResource($sourcingRequest)->response();
    }
}
