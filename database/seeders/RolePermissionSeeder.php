<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $modules = [
            'branches',
            'users',
            'roles',

            'brands',
            'brand_models',
            'calibers',
            'weapon_types',

            'weapons',
            'ammo',
            'accessory_categories',
            'accessories',

            'customers',
            'sales',
        ];

        $actions = [
            'view',
            'create',
            'update',
            'delete',
        ];

        $permissions = [];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $permissions[] = "{$action}_{$module}";
            }
        }

        $extraPermissions = [
            'confirm_sales',

            'view_inventory_movements',
            'create_inventory_movements',

            'view_reports',
            'export_reports',

            'view_dashboard',
        ];

        $permissions = array_values(array_unique([
            ...$permissions,
            ...$extraPermissions,
        ]));

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::firstOrCreate([
            'name' => 'Super Administrador',
            'guard_name' => 'web',
        ]);

        $admin = Role::firstOrCreate([
            'name' => 'Administrador',
            'guard_name' => 'web',
        ]);

        $inventory = Role::firstOrCreate([
            'name' => 'Inventario',
            'guard_name' => 'web',
        ]);

        $seller = Role::firstOrCreate([
            'name' => 'Vendedor',
            'guard_name' => 'web',
        ]);

        $viewer = Role::firstOrCreate([
            'name' => 'Consulta',
            'guard_name' => 'web',
        ]);

        $superAdmin->syncPermissions($permissions);

        $admin->syncPermissions([
            'view_branches',

            'view_users',
            'create_users',
            'update_users',

            'view_roles',
            'update_roles',

            'view_brands',
            'create_brands',
            'update_brands',

            'view_brand_models',
            'create_brand_models',
            'update_brand_models',

            'view_calibers',
            'create_calibers',
            'update_calibers',

            'view_weapon_types',
            'create_weapon_types',
            'update_weapon_types',

            'view_weapons',
            'create_weapons',
            'update_weapons',

            'view_ammo',
            'create_ammo',
            'update_ammo',

            'view_accessory_categories',
            'create_accessory_categories',
            'update_accessory_categories',

            'view_accessories',
            'create_accessories',
            'update_accessories',

            'view_customers',
            'create_customers',
            'update_customers',

            'view_sales',
            'create_sales',
            'update_sales',
            'confirm_sales',

            'view_inventory_movements',
            'create_inventory_movements',

            'view_reports',
            'export_reports',
            'view_dashboard',
        ]);

        $inventory->syncPermissions([
            'view_brands',
            'create_brands',
            'update_brands',

            'view_brand_models',
            'create_brand_models',
            'update_brand_models',

            'view_calibers',
            'create_calibers',
            'update_calibers',

            'view_weapon_types',
            'create_weapon_types',
            'update_weapon_types',

            'view_weapons',
            'create_weapons',
            'update_weapons',

            'view_ammo',
            'create_ammo',
            'update_ammo',

            'view_accessory_categories',
            'create_accessory_categories',
            'update_accessory_categories',

            'view_accessories',
            'create_accessories',
            'update_accessories',

            'view_inventory_movements',
            'create_inventory_movements',

            'view_reports',
            'view_dashboard',
        ]);

        $seller->syncPermissions([
            'view_weapons',
            'view_ammo',
            'view_accessories',

            'view_customers',
            'create_customers',
            'update_customers',

            'view_sales',
            'create_sales',
            'update_sales',
            'confirm_sales',

            'view_dashboard',
        ]);

        $viewer->syncPermissions([
            'view_brands',
            'view_brand_models',
            'view_calibers',
            'view_weapon_types',

            'view_weapons',
            'view_ammo',
            'view_accessory_categories',
            'view_accessories',

            'view_customers',
            'view_sales',

            'view_reports',
            'view_dashboard',
        ]);

        $firstUser = User::query()->first();

        if ($firstUser && !$firstUser->hasRole('Super Administrador')) {
            $firstUser->assignRole('Super Administrador');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}