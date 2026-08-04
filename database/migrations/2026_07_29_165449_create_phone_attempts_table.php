<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->string('provider_order_id')->nullable();
            $table->string('provider');
            $table->string('phone_number');
            $table->string('country_code');
            $table->string('sms_code')->nullable();
            $table->enum('status', ['waiting', 'received', 'expired', 'refunded'])->default('waiting');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['provider', 'provider_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_attempts');
    }
};
