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

            $table->unsignedBigInteger('goods_id');

            $table->string('marketplace')->default('plati');
            $table->string('marketplace_order_id');

            $table->decimal('refunded_amount', 18, 6)->default(0);

            $table->string('status')->default('pending');

            $table->timestamps();

            $table->index('goods_id');
            $table->unique(['marketplace', 'marketplace_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
