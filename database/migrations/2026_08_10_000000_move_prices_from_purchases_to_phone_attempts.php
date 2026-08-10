<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phone_attempts', function (Blueprint $table) {
            $table->decimal('sold_price', 18, 6)->default(0);
            $table->decimal('cost_price', 18, 6)->default(0);
            $table->decimal('marketplace_fee', 18, 6)->default(0);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['sold_price', 'cost_price', 'marketplace_fee']);
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('sold_price', 18, 6)->default(0);
            $table->decimal('cost_price', 18, 6)->default(0);
            $table->decimal('marketplace_fee', 18, 6)->default(0);
        });
        
        Schema::table('phone_attempts', function (Blueprint $table) {
            $table->dropColumn(['sold_price', 'cost_price', 'marketplace_fee']);
        });
    }
};
