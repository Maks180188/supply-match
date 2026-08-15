<?php

namespace App\Http\Controllers\Api\SupplierProposal;

use App\Actions\SupplierProposals\CreateSupplierProposal;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierProposalRequest;
use App\Http\Resources\SupplierProposalResource;
use App\Models\SourcingRequest;
use App\Models\SupplierProposal;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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

    public function index(SourcingRequest $sourcingRequest): AnonymousResourceCollection
    {
        Gate::authorize('viewProposals', $sourcingRequest);

        $proposals = $sourcingRequest->proposals()
            ->with('company')
            ->latest()
            ->paginate();

        return SupplierProposalResource::collection($proposals);
    }

    public function indexMine(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', SupplierProposal::class);

        $proposals = SupplierProposal::query()
            ->where('company_id', $request->user()->company_id)
            ->with('sourcingRequest')
            ->latest()
            ->paginate();

        return SupplierProposalResource::collection($proposals);
    }

    public function showMine(SupplierProposal $supplierProposal): SupplierProposalResource
    {
        Gate::authorize('view', $supplierProposal);

        $supplierProposal->load([
            'sourcingRequest.company',
            'sourcingRequest.category',
            'sourcingRequest.keywords',
        ]);

        return new SupplierProposalResource($supplierProposal);
    }

    public function show(SourcingRequest $sourcingRequest, SupplierProposal $supplierProposal): SupplierProposalResource
    {
        Gate::authorize('viewProposals', $sourcingRequest);

        if ($supplierProposal->sourcing_request_id !== $sourcingRequest->id) {
            abort(404);
        }

        $supplierProposal->load([
            'company',
            'sourcingRequest',
        ]);

        return new SupplierProposalResource($supplierProposal);
    }
}
