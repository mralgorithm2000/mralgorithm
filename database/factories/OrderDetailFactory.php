<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrderDetail> */
class OrderDetailFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $detail = fake()->randomElement([
            ['service', 'Service', fake()->randomElement(['Telegram', 'Instagram', 'Discord'])],
            ['quantity', 'Quantity', (string) fake()->numberBetween(1, 1000)],
            ['target', 'Target', fake()->url()],
            ['region', 'Region', fake()->countryCode()],
        ]);

        return [
            'order_id' => Order::factory(),
            'order_detail_key' => $detail[0],
            'order_detail_name' => $detail[1],
            'order_detail_value' => $detail[2],
        ];
    }
}
