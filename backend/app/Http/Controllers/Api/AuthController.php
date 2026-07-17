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
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

final class AuthController extends Controller
{
    /**
     * @throws Throwable
     */
    public function register(RegisterRequest $request, RegisterUser $registerUser): JsonResponse
    {
        $result = $registerUser->execute($request->validated());

        $user = $result['user'];
        $token = $result['token'];

        $user->load('company');

        return new UserResource($user)
            ->additional(['token' => $token])
            ->response()
            ->setStatusCode(201);
    }

    public function login(LoginRequest $request, LoginUser $loginUser): JsonResponse
    {
        $result = $loginUser->execute($request->validated());

        $user = $result['user'];
        $token = $result['token'];

        return new UserResource($user)
            ->additional(['token' => $token])
            ->response();
    }

    public function me(Request $request): UserResource
    {
        $user = $request->user();
        $user->load('company');

        return new UserResource($user);
    }

    public function logout(Request $request): Response
    {
        $token = $request->user()->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            abort(401);
        }

        $token->delete();

        return response()->noContent();
    }
}
