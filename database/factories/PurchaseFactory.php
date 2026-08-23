<?php

namespace Database\Factories;

use App\Enums\PurchaseStatus;
use App\Models\Good;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $costPrice = fake()->randomFloat(6, 1, 100);
        $soldPrice = fake()->randomFloat(6, $costPrice, $costPrice * 1.5);

        return [
            'goods_id' => Good::query()->inRandomOrder()->value('id'),
            'marketplace' => fake()->randomElement(['Plati', 'GGsel', 'Direct']),
            'marketplace_order_id' => fake()->unique()->uuid(),
            'sold_price' => $soldPrice,
            'cost_price' => $costPrice,
            'marketplace_fee' => fake()->randomFloat(6, 0, $soldPrice * 0.15),
            'refunded_amount' => 0,
            'status' => fake()->randomElement(PurchaseStatus::cases())->value,
        ];
    }
}
