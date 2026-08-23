<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for the goods record.');
            $table->foreignId('supplier_id')
                ->comment('Identifier of the supplier that provided the product.')
                ->constrained('suppliers')
                ->restrictOnDelete();
            $table->string('supplier_product_id')->comment('Product identifier assigned by the supplier.');
            $table->string('channel')->comment('Identifier of the sales channel through which the product was sold, such as Plati or GGsel.');
            $table->decimal('original_price', 18, 6)->comment('Supplier price recorded after the product was purchased.');
            $table->decimal('selling_price', 18, 6)->comment('Price at which the product was sold through the sales channel.');
            $table->string('name')->comment('Name of the product.');
            $table->foreignId('type_id')
                ->comment('Identifier of the related product type.')
                ->constrained('types')
                ->restrictOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'supplier_product_id']);
            $table->index('channel');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreign('goods_id')
                ->references('id')
                ->on('goods')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['goods_id']);
        });

        Schema::dropIfExists('goods');
    }
};
