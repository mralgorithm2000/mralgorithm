<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE suppliers MODIFY status ENUM('active', 'deactive', 'inactive') NOT NULL DEFAULT 'active'");
        DB::table('suppliers')->where('status', 'deactive')->update(['status' => 'inactive']);
        DB::statement("ALTER TABLE suppliers MODIFY status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE suppliers MODIFY status ENUM('active', 'inactive', 'deactive') NOT NULL DEFAULT 'active'");
        DB::table('suppliers')->where('status', 'inactive')->update(['status' => 'deactive']);
        DB::statement("ALTER TABLE suppliers MODIFY status ENUM('active', 'deactive') NOT NULL DEFAULT 'active'");
    }
};
