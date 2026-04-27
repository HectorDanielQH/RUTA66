<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seed the application's roles and permissions.
     */
    public function run(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdminRole->syncPermissions(Permission::all());

        $cashierRole = Role::firstOrCreate(['name' => 'cajero']);
        $cashierRole->syncPermissions(Permission::query()
            ->whereIn('name', [
                'ViewAny:CashRegister',
                'View:CashRegister',
                'Create:CashRegister',
                'Update:CashRegister',
                'ViewAny:Customer',
                'View:Customer',
                'Create:Customer',
                'Update:Customer',
                'ViewAny:Order',
                'View:Order',
                'Create:Order',
                'Update:Order',
                'ViewAny:Product',
                'View:Product',
                'ViewAny:Category',
                'View:Category',
                'ViewAny:DeliveryZone',
                'View:DeliveryZone',
            ])
            ->get());
    }
}
