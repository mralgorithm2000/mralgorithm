<?php

namespace Database\Seeders;

use App\Models\Good;
use App\Models\Type;
use Illuminate\Database\Seeder;

class GoodSeeder extends Seeder
{
    public function run(): void
    {
        $types = Type::query()->whereIn('title', [
            'Virtual Number',
            'SMM',
            'Gift Cards',
            'Vouchers',
        ])->pluck('id', 'title');
        $goods = [
            [
                'name' => 'Virtual Number',
                'type_id' => $types['Virtual Number'],
            ],
            [
                'name' => 'SMM Service',
                'type_id' => $types['SMM'],
            ],
            [
                'name' => 'Gift Card',
                'type_id' => $types['Gift Cards'],
            ],
            [
                'name' => 'Voucher',
                'type_id' => $types['Vouchers'],
            ],
        ];

        foreach ($goods as $good) {
            Good::updateOrCreate(
                ['name' => $good['name']],
                $good,
            );
        }
    }
}
