<?php

use App\Http\Controllers\Api\Admin\SourcingRequestController as AdminSourcingRequestController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Category\CategoryController;
use App\Http\Controllers\Api\SourcingRequestController;
use App\Http\Controllers\Api\SupplierProposal\SupplierProposalController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'supply-match-api',
    ]);
});

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/sourcing-requests', [SourcingRequestController::class, 'index']);
Route::get('/sourcing-requests/{sourcingRequest}', [SourcingRequestController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/sourcing-requests', [SourcingRequestController::class, 'store']);
    Route::post('/sourcing-requests/{sourcingRequest}/submit', [SourcingRequestController::class, 'submit']);
    Route::post('/admin/sourcing-requests/{sourcingRequest}/publish', [AdminSourcingRequestController::class, 'publish']);
    Route::post('/admin/sourcing-requests/{sourcingRequest}/reject', [AdminSourcingRequestController::class, 'reject']);
    Route::get('/admin/sourcing-requests', [AdminSourcingRequestController::class, 'index']);
    Route::get('/admin/sourcing-requests/{sourcingRequest}', [AdminSourcingRequestController::class, 'show']);
    Route::get('/my/sourcing-requests', [SourcingRequestController::class, 'mine']);
    Route::get('/my/sourcing-requests/{sourcingRequest}', [SourcingRequestController::class, 'showMine']);
    Route::put('/my/sourcing-requests/{sourcingRequest}', [SourcingRequestController::class, 'update']);
    Route::post('/sourcing-requests/{sourcingRequest}/proposals', [SupplierProposalController::class, 'store']);
    Route::get('/my/sourcing-requests/{sourcingRequest}/proposals', [SupplierProposalController::class, 'index']);
    Route::get('/my/sourcing-requests/{sourcingRequest}/proposals/{supplierProposal}', [SupplierProposalController::class, 'show']);
    Route::get('/my/proposals', [SupplierProposalController::class, 'indexMine']);
    Route::get('/my/proposals/{supplierProposal}', [SupplierProposalController::class, 'showMine']);
});
