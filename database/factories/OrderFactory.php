<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $costPrice = fake()->randomFloat(6, 1, 100);

        return [
            'purchase_id' => Purchase::query()->inRandomOrder()->value('id'),
            'supplier_order_id' => fake()->unique()->numerify('SUP-########'),
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed']),
            'sold_price' => fake()->randomFloat(6, $costPrice, $costPrice * 1.5),
            'cost_price' => $costPrice,
        ];
    }
}
