<?php

namespace App\Actions\Auth;

use App\Enums\UserRole;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Throwable;

final class RegisterUser
{
    /**
     * @throws Throwable
     */
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $company = Company::create([
                'name' => $data['company_name'],
                'type' => $data['company_type'],
            ]);

            $user = $company->users()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => UserRole::from($data['company_type']),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
            ];
        });
    }
}
