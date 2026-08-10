<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (! Schema::hasColumn('purchases', 'sold_price')) {
                $table->decimal('sold_price', 18, 6)->default(0);
            }
            if (! Schema::hasColumn('purchases', 'cost_price')) {
                $table->decimal('cost_price', 18, 6)->default(0);
            }
            if (! Schema::hasColumn('purchases', 'marketplace_fee')) {
                $table->decimal('marketplace_fee', 18, 6)->default(0);
            }
        });
    }

    public function down(): void
    {
        // The pricing columns predate this compatibility migration.
    }
};
