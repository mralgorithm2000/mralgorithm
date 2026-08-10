<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('number_orders')) {
            return;
        }

        DB::table('number_orders')
            ->orderBy('id')
            ->each(function (object $order): void {
                $purchaseId = DB::table('purchases')->insertGetId([
                    'purchasable_id' => $order->virtual_number_id,
                    'purchasable_type' => 'virtual_number',
                    'marketplace' => 'plati',
                    'external_order_id' => $order->plati_order_id,
                    'status' => 'pending',
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ]);

                $provider = DB::table('virtual_numbers')
                    ->where('id', $order->virtual_number_id)
                    ->value('source') ?? 'unknown';

                DB::table('phone_attempts')->insert([
                    'id' => $order->id,
                    'purchase_id' => $purchaseId,
                    'provider_order_id' => $order->source_order_id,
                    'provider' => $provider,
                    'phone_number' => $order->phone_number,
                    'country_code' => $order->country_code,
                    'sms_code' => $order->sms_code,
                    'status' => $order->status,
                    'expires_at' => $order->expires_at,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ]);
            });

        Schema::drop('number_orders');
    }

    public function down(): void
    {
        // The legacy mixed-responsibility table is intentionally not restored.
    }
};
