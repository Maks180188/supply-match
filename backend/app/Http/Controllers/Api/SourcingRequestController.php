<?php

namespace App\Http\Controllers\Api;

use App\Actions\SourcingRequests\CreateSourcingRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSourcingRequestRequest;
use App\Http\Resources\SourcingRequestResource;
use App\Models\SourcingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SourcingRequestController extends Controller
{
    /**
     * @throws Throwable
     */
    public function store(StoreSourcingRequestRequest $request, CreateSourcingRequest $createSourcingRequest): JsonResponse
    {
        Gate::authorize('create', SourcingRequest::class);
        $sourcingRequest = $createSourcingRequest->execute($request->user(), $request->validated());

        return new SourcingRequestResource($sourcingRequest)->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
