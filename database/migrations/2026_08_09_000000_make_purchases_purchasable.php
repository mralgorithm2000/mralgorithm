<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('purchasable_id');
            $table->string('purchasable_type');

            $table->string('marketplace')->default('plati');
            $table->string('external_order_id');

            $table->decimal('sold_price', 18, 6)->default(0);
            $table->decimal('cost_price', 18, 6)->default(0);
            $table->decimal('marketplace_fee', 18, 6)->default(0);
            $table->decimal('refunded_amount', 18, 6)->default(0);

            $table->string('status')->default('pending');

            $table->timestamps();

            $table->index(
                ['purchasable_type', 'purchasable_id'],
                'purchases_purchasable_type_purchasable_id_index'
            );

            $table->unique(['marketplace', 'external_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};    
