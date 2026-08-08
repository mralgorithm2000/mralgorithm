<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the administrator account configured in the environment.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => config('auth.admin.email')],
            [
                'name' => config('auth.admin.name'),
                'password' => config('auth.admin.password'),
                'email_verified_at' => now(),
            ],
        );
    }
}
