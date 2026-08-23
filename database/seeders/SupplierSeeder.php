<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['smscodex', 'fazercards', 'numberland', 'followeran'] as $title) {
            Supplier::updateOrCreate(
                ['title' => $title],
                [
                    'website_url' => "https://{$title}.com",
                    'status' => 'active',
                ],
            );
        }
    }
}
