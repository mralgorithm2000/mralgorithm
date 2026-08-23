<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_marketplace_mappings', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for the goods marketplace mapping.');
            $table->foreignId('good_id')
                ->comment('Identifier of the related good.')
                ->constrained('goods')
                ->cascadeOnDelete();
            $table->string('marketplace')
                ->index()
                ->comment('Marketplace associated with the external product.');
            $table->unsignedBigInteger('marketplace_product_id')
                ->index()
                ->comment('Product identifier assigned by the marketplace.');
            $table->timestamps();

            $table->unique(['good_id', 'marketplace']);
        });

        $now = now();
        DB::table('goods')
            ->whereNotNull('marketplace_product_id')
            ->orderBy('id')
            ->get()
            ->each(function (object $good) use ($now): void {
                DB::table('goods_marketplace_mappings')->insert([
                    'good_id' => $good->id,
                    'marketplace' => 'plati',
                    'marketplace_product_id' => $good->marketplace_product_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });

        Schema::table('goods', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropIndex(['supplier_id', 'supplier_product_id']);
            $table->dropIndex(['channel']);
            $table->dropUnique(['marketplace_product_id']);
            $table->dropColumn([
                'marketplace_product_id',
                'channel',
                'original_price',
                'selling_price',
                'supplier_id',
                'supplier_product_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('goods', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->restrictOnDelete();
            $table->string('supplier_product_id')->nullable();
            $table->unsignedBigInteger('marketplace_product_id')->nullable()->unique();
            $table->string('channel')->nullable();
            $table->decimal('original_price', 18, 6)->nullable();
            $table->decimal('selling_price', 18, 6)->nullable();

            $table->index(['supplier_id', 'supplier_product_id']);
            $table->index('channel');
        });

        DB::table('goods_marketplace_mappings')
            ->where('marketplace', 'plati')
            ->orderBy('id')
            ->get()
            ->each(function (object $mapping): void {
                DB::table('goods')
                    ->where('id', $mapping->good_id)
                    ->update(['marketplace_product_id' => $mapping->marketplace_product_id]);
            });

        Schema::dropIfExists('goods_marketplace_mappings');
    }
};
