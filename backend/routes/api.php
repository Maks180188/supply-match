<?php

use App\Http\Controllers\Api\Admin\SourcingRequestController as AdminSourcingRequestController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SourcingRequestController;
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

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/sourcing-requests', [SourcingRequestController::class, 'store']);
    Route::post('/sourcing-requests/{sourcingRequest}/submit', [SourcingRequestController::class, 'submit']);
    Route::post('/admin/sourcing-requests/{sourcingRequest}/publish', [AdminSourcingRequestController::class, 'publish']);
    Route::post('/admin/sourcing-requests/{sourcingRequest}/reject', [AdminSourcingRequestController::class, 'reject']);
});
