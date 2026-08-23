<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('sold_price', 18, 6)->default(0)->after('marketplace_order_id');
            $table->decimal('cost_price', 18, 6)->default(0)->after('sold_price');
            $table->decimal('marketplace_fee', 18, 6)->default(0)->after('cost_price');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['sold_price', 'cost_price', 'marketplace_fee']);
        });
    }
};
