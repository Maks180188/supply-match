<?php

namespace App\Http\Controllers\Api\SupplierProposal;

use App\Actions\SupplierProposals\CreateSupplierProposal;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierProposalRequest;
use App\Http\Resources\SupplierProposalResource;
use App\Models\SourcingRequest;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SupplierProposalController extends Controller
{
    public function store(
        StoreSupplierProposalRequest $request,
        SourcingRequest $sourcingRequest,
        CreateSupplierProposal $createSupplierProposal
    ): JsonResponse {
        Gate::authorize('propose', $sourcingRequest);

        try {
            $supplierProposal = $createSupplierProposal->execute(
                $request->user(),
                $sourcingRequest,
                $request->validated()
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }

        return new SupplierProposalResource($supplierProposal)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
