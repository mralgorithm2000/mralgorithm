<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('number_orders', function (Blueprint $table) {

            $table->id();

            $table->foreignId('virtual_number_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('plati_order_id')
                ->unique();

            $table->string('phone_number');

            $table->string('country_code');

            $table->string('sms_code')
                ->nullable();

            $table->enum('status', [
                'waiting',
                'received',
                'expired',
                'refunded',
            ])->default('waiting');

            $table->timestamp('expires_at')
                ->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('number_orders');
    }
};
