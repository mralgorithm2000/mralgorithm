<?php

namespace Tests\Feature\Api;

use App\Models\Good;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Type;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_and_show_require_authentication(): void
    {
        $this->getJson('/api/orders')->assertUnauthorized();
        $this->getJson('/api/orders/1')->assertUnauthorized();
    }

    public function test_index_requires_orders_list_permission(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/orders')->assertForbidden();
    }

    public function test_show_requires_orders_view_permission(): void
    {
        $order = $this->createOrder('SUP-100', 'pending');
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/orders/{$order->id}")->assertForbidden();
    }

    public function test_index_is_paginated_and_does_not_include_order_details(): void
    {
        $this->actingAsUserWith('orders_list');
        $first = $this->createOrder('SUP-100', 'pending');
        $first->orderDetails()->create($this->detailData());
        $second = $this->createOrder('SUP-200', 'completed');

        $this->getJson('/api/orders?per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissingPath('data.0.order_details')
            ->assertJsonPath('data.0.good_name', 'Order Test Good')
            ->assertJsonPath('data.0.marketplace', $second->purchase->marketplace)
            ->assertJsonPath('data.0.marketplace_order_id', $second->purchase->marketplace_order_id)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonStructure([
                'data' => [[
                    'id', 'purchase_id', 'supplier_order_id', 'good_name',
                    'marketplace', 'marketplace_order_id', 'status', 'sold_price',
                    'cost_price', 'created_at', 'updated_at',
                ]],
                'links', 'meta',
            ]);
    }

    public function test_index_filters_by_purchase_id_supplier_order_id_and_status(): void
    {
        $this->actingAsUserWith('orders_list');
        $first = $this->createOrder('SUP-100', 'pending');
        $second = $this->createOrder('SUP-200', 'completed');
        $this->createOrder('SUP-300', 'failed');

        $this->getJson("/api/orders?purchase_id={$first->purchase_id}")
            ->assertOk()->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $first->id);

        $this->getJson('/api/orders?supplier_order_id=SUP-200')
            ->assertOk()->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $second->id);

        $this->getJson('/api/orders?status=failed')
            ->assertOk()->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.supplier_order_id', 'SUP-300');
    }

    public function test_index_filters_by_good_name_marketplace_and_marketplace_order_id(): void
    {
        $this->actingAsUserWith('orders_list');
        $matching = $this->createOrder('SUP-100', 'pending');

        $otherGood = Good::create([
            'name' => 'Discord Nitro',
            'type_id' => Type::firstOrCreate(['title' => 'Digital'])->id,
        ]);
        $otherPurchase = Purchase::factory()->create([
            'goods_id' => $otherGood->id,
            'marketplace' => 'Different Marketplace',
        ]);
        Order::factory()->create([
            'purchase_id' => $otherPurchase->id,
            'supplier_order_id' => 'SUP-200',
        ]);

        $this->getJson('/api/orders?good_name=Order%20Test')
            ->assertOk()->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $matching->id);

        $marketplace = urlencode($matching->purchase->marketplace);
        $this->getJson("/api/orders?marketplace={$marketplace}")
            ->assertOk()->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $matching->id);

        $marketplaceOrderId = urlencode($matching->purchase->marketplace_order_id);
        $this->getJson("/api/orders?marketplace_order_id={$marketplaceOrderId}")
            ->assertOk()->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $matching->id);
    }

    public function test_show_includes_order_details(): void
    {
        $this->actingAsUserWith('orders_view');
        $order = $this->createOrder('SUP-100', 'completed');
        $detail = $order->orderDetails()->create($this->detailData());
        $goodDetail = $order->purchase->good->details()->create([
            'good_key' => 'region',
            'good_name' => 'Region',
            'good_value' => 'Europe',
        ]);

        $this->getJson("/api/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.supplier_order_id', 'SUP-100')
            ->assertJsonPath('data.good_name', 'Order Test Good')
            ->assertJsonPath('data.marketplace', $order->purchase->marketplace)
            ->assertJsonPath('data.marketplace_order_id', $order->purchase->marketplace_order_id)
            ->assertJsonPath('data.purchase.id', $order->purchase_id)
            ->assertJsonPath('data.purchase.good.id', $order->purchase->good->id)
            ->assertJsonPath('data.purchase.good.name', 'Order Test Good')
            ->assertJsonPath('data.purchase.good.type.title', 'Digital')
            ->assertJsonPath('data.purchase.good.details.0.id', $goodDetail->id)
            ->assertJsonPath('data.purchase.good.details.0.good_key', 'region')
            ->assertJsonPath('data.order_details.0.id', $detail->id)
            ->assertJsonPath('data.order_details.0.order_detail_key', 'service')
            ->assertJsonStructure(['data' => [
                'id', 'purchase_id', 'supplier_order_id', 'good_name',
                'marketplace', 'marketplace_order_id', 'status', 'sold_price', 'cost_price',
                'created_at', 'updated_at', 'order_details' => [[
                    'id', 'order_id', 'order_detail_key', 'order_detail_name',
                    'order_detail_value', 'created_at', 'updated_at',
                ]], 'purchase' => [
                    'id', 'marketplace', 'marketplace_order_id', 'goods_id', 'sold_price',
                    'cost_price', 'marketplace_fee', 'refunded_amount', 'status',
                    'created_at', 'updated_at', 'good',
                ],
            ]]);
    }

    private function actingAsUserWith(string $permission): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        Sanctum::actingAs($user);
    }

    private function createOrder(string $supplierOrderId, string $status): Order
    {
        $purchase = Purchase::factory()->create(['goods_id' => $this->good()->id]);

        return Order::factory()->create([
            'purchase_id' => $purchase->id,
            'supplier_order_id' => $supplierOrderId,
            'status' => $status,
        ]);
    }

    private function good(): Good
    {
        return Good::firstOrCreate(
            ['name' => 'Order Test Good'],
            [
                'type_id' => Type::firstOrCreate(['title' => 'Digital'])->id,
            ],
        );
    }

    /** @return array<string, string> */
    private function detailData(): array
    {
        return [
            'order_detail_key' => 'service',
            'order_detail_name' => 'Service',
            'order_detail_value' => 'Telegram',
        ];
    }
}
