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
        Schema::table('virtual_numbers', function (Blueprint $table) {
            $table->string('service_id')->nullable()->after('plati_id');
            $table->string('country_id')->nullable()->after('service_id');
            $table->string('country_code')->nullable()->after('country_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('virtual_numbers', function (Blueprint $table) {
            $table->dropColumn('service_id');
            $table->dropColumn('country_id');
            $table->dropColumn('country_code');
        });
    }
};
