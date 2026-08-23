<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods', function (Blueprint $table) {
            $table->unsignedBigInteger('marketplace_product_id')
                ->nullable()
                ->unique()
                ->after('supplier_product_id');
        });
    }

    public function down(): void
    {
        Schema::table('goods', function (Blueprint $table) {
            $table->dropUnique(['marketplace_product_id']);
            $table->dropColumn('marketplace_product_id');
        });
    }
};
