<?php

namespace Database\Seeders;

use App\Models\Good;
use App\Models\Purchase;
use Illuminate\Database\Seeder;
use LogicException;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $goodIds = Good::query()->pluck('id');

        if ($goodIds->isEmpty()) {
            throw new LogicException('Goods must be seeded before purchases.');
        }

        foreach (range(1, 20) as $number) {
            $marketplace = ['Plati', 'GGsel', 'Direct'][($number - 1) % 3];
            $attributes = Purchase::factory()->make([
                'goods_id' => $goodIds->random(),
                'marketplace' => $marketplace,
                'marketplace_order_id' => sprintf('SEEDED-%05d', $number),
            ])->getAttributes();

            Purchase::updateOrCreate(
                [
                    'marketplace' => $marketplace,
                    'marketplace_order_id' => sprintf('SEEDED-%05d', $number),
                ],
                $attributes,
            );
        }
    }
}
