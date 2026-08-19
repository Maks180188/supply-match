<?php

namespace App\Http\Controllers\Api;

use App\Actions\Auth\LoginUser;
use App\Actions\Auth\RegisterUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Throwable;

final class AuthController extends Controller
{
    /**
     * @throws Throwable
     */
    public function register(RegisterRequest $request, RegisterUser $registerUser): JsonResponse
    {
        $user = $registerUser->execute($request->validated());
        $request->session()->regenerate();
        $user->load('company');

        return new UserResource($user)->response()->setStatusCode(201);
    }

    public function login(LoginRequest $request, LoginUser $loginUser): JsonResponse
    {
        $user = $loginUser->execute($request->validated());
        $request->session()->regenerate();

        return new UserResource($user)->response();
    }

    public function me(Request $request): UserResource
    {
        $user = $request->user();
        $user->load('company');

        return new UserResource($user);
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
