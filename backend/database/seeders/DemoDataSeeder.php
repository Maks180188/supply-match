<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\SourcingRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::factory()->count(6)->create();
        $companies = Company::factory()->buyer()->count(4)->create();

        foreach ($companies as $company) {
            $buyer = User::factory()->create([
                'company_id' => $company->id,
            ]);

            SourcingRequest::factory()->published()->count(5)->create([
                'company_id' => $company->id,
                'created_by' => $buyer->id,
                'category_id' => fn (): int => $categories->random()->id,
            ]);
        }
    }
}
