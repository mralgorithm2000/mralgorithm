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

class PurchaseApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/purchases')->assertUnauthorized();
        $this->getJson('/api/purchases/1')->assertUnauthorized();
    }

    public function test_user_without_purchase_list_permission_is_forbidden(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/purchases')->assertForbidden();
    }

    public function test_authorized_user_receives_paginated_purchases_with_the_good_name(): void
    {
        $this->actingAsUserWithPurchaseList();
        $good = $this->createGood('Telegram Stars');
        $this->createPurchase($good, 'Plati', '12345');
        $this->createPurchase($good, 'GGsel', '67890');

        $this->getJson('/api/purchases?per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.marketplace', 'GGsel')
            ->assertJsonPath('data.0.good.name', 'Telegram Stars')
            ->assertJsonPath('data.0.sold_price', '12.500000')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonStructure([
                'data' => [[
                    'id', 'marketplace', 'marketplace_order_id', 'goods_id', 'sold_price',
                    'cost_price', 'marketplace_fee', 'refunded_amount', 'status', 'good' => ['name'],
                ]],
                'links', 'meta',
            ]);
    }

    public function test_marketplace_filter_is_applied(): void
    {
        $this->actingAsUserWithPurchaseList();
        $good = $this->createGood('Telegram Stars');
        $this->createPurchase($good, 'Plati', '12345');
        $this->createPurchase($good, 'GGsel', '67890');

        $this->getJson('/api/purchases?marketplace=Plati')
            ->assertOk()->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.marketplace_order_id', '12345');
    }

    public function test_marketplace_order_id_filter_is_applied(): void
    {
        $this->actingAsUserWithPurchaseList();
        $good = $this->createGood('Telegram Stars');
        $this->createPurchase($good, 'Plati', '12345');
        $this->createPurchase($good, 'Plati', '67890');

        $this->getJson('/api/purchases?marketplace_order_id=12345')
            ->assertOk()->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.marketplace_order_id', '12345');
    }

    public function test_goods_filter_matches_the_related_good_name(): void
    {
        $this->actingAsUserWithPurchaseList();
        $this->createPurchase($this->createGood('Telegram Stars'), 'Plati', '12345');
        $this->createPurchase($this->createGood('Discord Nitro'), 'Plati', '67890');

        $this->getJson('/api/purchases?goods=Telegram')
            ->assertOk()->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.good.name', 'Telegram Stars');
    }

    public function test_show_requires_purchase_view_permission(): void
    {
        $purchase = $this->createPurchase($this->createGood('Telegram Stars'), 'Plati', '12345');
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/purchases/{$purchase->id}")->assertForbidden();
    }

    public function test_authorized_user_can_show_purchase_with_good_details_and_orders(): void
    {
        $this->actingAsUserWith('purchase_view');
        $good = $this->createGood('Telegram Stars');
        $good->details()->create([
            'good_key' => 'region',
            'good_name' => 'Region',
            'good_value' => 'Europe',
        ]);
        $purchase = $this->createPurchase($good, 'Plati', '12345');
        $order = Order::factory()->create([
            'purchase_id' => $purchase->id,
            'supplier_order_id' => 'SUP-12345',
        ]);
        $order->orderDetails()->create([
            'order_detail_key' => 'service',
            'order_detail_name' => 'Service',
            'order_detail_value' => 'Telegram',
        ]);

        $this->getJson("/api/purchases/{$purchase->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $purchase->id)
            ->assertJsonPath('data.good.name', 'Telegram Stars')
            ->assertJsonPath('data.good.type.title', 'Digital')
            ->assertJsonPath('data.good.details.0.good_key', 'region')
            ->assertJsonPath('data.orders.0.id', $order->id)
            ->assertJsonPath('data.orders.0.order_details.0.order_detail_key', 'service')
            ->assertJsonStructure(['data' => [
                'id', 'marketplace', 'marketplace_order_id', 'goods_id', 'sold_price',
                'cost_price', 'marketplace_fee', 'refunded_amount', 'status', 'created_at',
                'updated_at', 'good', 'orders',
            ]]);
    }

    private function actingAsUserWithPurchaseList(): void
    {
        $this->actingAsUserWith('purchase_list');
    }

    private function actingAsUserWith(string $permission): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        Sanctum::actingAs($user);
    }

    private function createGood(string $name): Good
    {
        return Good::create([
            'name' => $name,
            'type_id' => Type::firstOrCreate(['title' => 'Digital'])->id,
        ]);
    }

    private function createPurchase(Good $good, string $marketplace, string $orderId): Purchase
    {
        return Purchase::create([
            'goods_id' => $good->id,
            'marketplace' => $marketplace,
            'marketplace_order_id' => $orderId,
            'sold_price' => 12.5,
            'cost_price' => 10,
            'marketplace_fee' => 1.25,
            'refunded_amount' => 0,
            'status' => 'pending',
        ]);
    }
}
