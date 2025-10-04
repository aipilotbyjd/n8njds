<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define and create permissions
        $permissions = [
            'workflows.create',
            'workflows.read',
            'workflows.update',
            'workflows.delete',
            'admin.access', // For accessing a future admin dashboard
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create a standard user role and assign specific permissions
        $userRole = Role::create(['name' => 'user']);
        $userRole->givePermissionTo([
            'workflows.create',
            'workflows.read',
            'workflows.update',
            'workflows.delete',
        ]);

        // Create an admin role
        $adminRole = Role::create(['name' => 'admin']);
        // Admins get all permissions implicitly via a Gate defined in a service provider
        // This is more robust than assigning every permission manually.
    }
}
