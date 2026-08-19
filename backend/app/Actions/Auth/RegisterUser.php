<?php

namespace App\Actions\Auth;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

final class RegisterUser
{
    /**
     * @throws Throwable
     */
    public function execute(array $data): User
    {
        $user = DB::transaction(function () use ($data): User {
            $company = Company::create([
                'name' => $data['company_name'],
                'type' => $data['company_type'],
            ]);

            return $company->users()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => UserRole::from($data['company_type']),
            ]);
        });

        Auth::login($user);

        return $user;
    }
}
