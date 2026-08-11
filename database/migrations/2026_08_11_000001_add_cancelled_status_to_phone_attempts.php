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
            $table->enum('status', ['waiting', 'received', 'expired', 'cancelled', 'refunded'])
                ->default('waiting')
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('phone_attempts')
            ->where('status', 'cancelled')
            ->update(['status' => 'expired']);

        Schema::table('phone_attempts', function (Blueprint $table) {
            $table->enum('status', ['waiting', 'received', 'expired', 'refunded'])
                ->default('waiting')
                ->change();
        });
    }
};
