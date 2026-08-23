<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Purchase;
use Database\Factories\OrderDetailFactory;
use Illuminate\Database\Seeder;
use LogicException;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $purchaseIds = Purchase::query()->pluck('id');
        if ($purchaseIds->isEmpty()) {
            throw new LogicException('Purchases must be seeded before orders.');
        }

        foreach (range(1, 20) as $number) {
            $supplierOrderId = sprintf('SEEDED-SUP-%05d', $number);
            $order = Order::updateOrCreate(
                ['supplier_order_id' => $supplierOrderId],
                Order::factory()->make([
                    'purchase_id' => $purchaseIds->random(),
                    'supplier_order_id' => $supplierOrderId,
                ])->getAttributes(),
            );

            $order->orderDetails()->delete();
            $order->orderDetails()->createMany(
                OrderDetailFactory::new()->count(fake()->numberBetween(2, 4))->make([
                    'order_id' => $order->id,
                ])->map->only([
                    'order_detail_key', 'order_detail_name', 'order_detail_value',
                ])->all(),
            );
        }
    }
}
