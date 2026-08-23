<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parameter_options', function (Blueprint $table) {
            $table->decimal('original_price', 18, 6)->nullable();
            $table->decimal('selling_price', 18, 6)->nullable();
            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->restrictOnDelete();
            $table->string('supplier_product_id')->nullable();

            $table->index(['supplier_id', 'supplier_product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('parameter_options', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropIndex(['supplier_id', 'supplier_product_id']);
            $table->dropColumn([
                'original_price',
                'selling_price',
                'supplier_id',
                'supplier_product_id',
            ]);
        });
    }
};
