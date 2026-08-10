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
            $table->unsignedBigInteger('purchasable_id')->nullable();
            $table->string('purchasable_type')->nullable();
            $table->string('marketplace')->default('plati');
            $table->string('external_order_id')->nullable();
            $table->decimal('sold_price', 18, 6)->default(0);
            $table->decimal('cost_price', 18, 6)->default(0);
            $table->decimal('marketplace_fee', 18, 6)->default(0);
            $table->decimal('refunded_amount', 18, 6)->default(0);
        });

        DB::table('purchases')->update([
            'purchasable_id' => DB::raw('virtual_number_id'),
            'purchasable_type' => 'virtual_number',
            'external_order_id' => DB::raw('plati_order_id'),
        ]);

        Schema::table('purchases', function (Blueprint $table) {
            $table->index(['purchasable_type', 'purchasable_id'], 'purchases_purchasable_type_purchasable_id_index');
            $table->unique(['marketplace', 'external_order_id']);
            $table->dropForeign(['virtual_number_id']);
            $table->dropColumn(['virtual_number_id', 'plati_order_id']);
        });

        // MariaDB can enforce these after the backfill without rebuilding the table
        // through Laravel's legacy column introspection path.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE purchases MODIFY purchasable_id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE purchases MODIFY purchasable_type VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE purchases MODIFY external_order_id VARCHAR(255) NOT NULL');
            DB::statement("ALTER TABLE purchases MODIFY status VARCHAR(255) NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('virtual_number_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('plati_order_id')->nullable();
        });

        DB::table('purchases')->where('purchasable_type', 'virtual_number')->update([
            'virtual_number_id' => DB::raw('purchasable_id'),
            'plati_order_id' => DB::raw('external_order_id'),
        ]);

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique(['marketplace', 'external_order_id']);
            $table->dropIndex('purchases_purchasable_type_purchasable_id_index');
            $table->dropColumn([
                'purchasable_id', 'purchasable_type', 'marketplace', 'external_order_id',
                'sold_price', 'cost_price', 'marketplace_fee', 'refunded_amount',
            ]);
        });
    }
};
