<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /** @var list<string> */
    public const PERMISSIONS = [
        'role_add',
        'role_edit',
        'role_list',
        'role_delete',
        'user_add',
        'user_edit',
        'user_list',
        'user_delete',
        'purchase_view',
        'purchase_list',
        'orders_view',
        'orders_list',
        'add_type',
        'edit_type',
        'list_types',
        'delete_type',
        'good_add',
        'good_edit',
        'good_list',
        'good_view',
        'good_delete',
        'good_add_plati_detail',
        'good_manage_plati_parameters',
        'good_manage_parameters',
        'supplier_add',
        'supplier_edit',
        'supplier_list',
        'supplier_delete',
        'refund_list',
        'refund_view',
        'refund_status',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
