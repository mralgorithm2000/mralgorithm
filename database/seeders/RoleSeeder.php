<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions(Permission::query()->where('guard_name', 'web')->get());
    }
}
