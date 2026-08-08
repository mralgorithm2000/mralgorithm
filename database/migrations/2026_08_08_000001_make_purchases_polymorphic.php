<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->unsignedBigInteger('purchasable_id')->nullable()->after('id');
            $table->string('purchasable_type')->nullable()->after('purchasable_id');
            $table->string('marketplace')->default('plati')->after('purchasable_type');
            $table->string('external_order_id')->nullable()->after('marketplace');
            $table->decimal('sold_price', 18, 6)->default(0)->after('external_order_id');
            $table->decimal('cost_price', 18, 6)->default(0)->after('sold_price');
            $table->decimal('marketplace_fee', 18, 6)->default(0)->after('cost_price');
            $table->decimal('refunded_amount', 18, 6)->default(0)->after('marketplace_fee');
            $table->string('status')->default('pending')->after('refunded_amount');
            $table->index(['purchasable_type', 'purchasable_id'], 'purchases_purchasable_index');
        });

        DB::table('purchases')
            ->whereNotNull('virtual_number_id')
            ->update([
                'purchasable_type' => 'virtual_number',
                'purchasable_id' => DB::raw('virtual_number_id'),
                'external_order_id' => DB::raw('plati_order_id'),
                'marketplace' => 'plati',
            ]);

        Schema::table('purchases', function (Blueprint $table) {
            $table->unique(['marketplace', 'external_order_id'], 'purchases_marketplace_external_order_unique');

            $table->dropUnique(['plati_order_id']);
            $table->dropForeign(['virtual_number_id']);
            $table->dropColumn(['virtual_number_id', 'plati_order_id']);
        });
    }

    public function down(): void
    {
        // This migration is forward-only.
    }
};
