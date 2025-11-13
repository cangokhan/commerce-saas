<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User permissions
            'view users',
            'create users',
            'edit users',
            'delete users',
            
            // Store permissions
            'view stores',
            'create stores',
            'edit stores',
            'delete stores',
            'manage own store',
            
            // Product permissions
            'view products',
            'create products',
            'edit products',
            'delete products',
            'manage own products',
            
            // Order permissions
            'view orders',
            'create orders',
            'edit orders',
            'delete orders',
            'manage own orders',
            
            // Import permissions
            'import products',
            'import orders',
            'export data',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles
        $superadminRole = Role::create(['name' => 'superadmin']);
        $vendorRole = Role::create(['name' => 'vendor']);
        $customerRole = Role::create(['name' => 'customer']);

        // Assign all permissions to superadmin
        $superadminRole->givePermissionTo(Permission::all());

        // Assign permissions to vendor
        $vendorRole->givePermissionTo([
            'view stores',
            'manage own store',
            'view products',
            'create products',
            'edit products',
            'delete products',
            'manage own products',
            'view orders',
            'create orders',
            'edit orders',
            'manage own orders',
            'import products',
            'export data',
        ]);

        // Assign permissions to customer
        $customerRole->givePermissionTo([
            'view products',
            'view stores',
            'create orders',
            'view orders',
            'manage own orders',
        ]);

        // Create superadmin user
        $superadmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@commerce-saas.com',
            'password' => Hash::make('password'),
            'tenant_id' => null, // Superadmin has no tenant
        ]);

        $superadmin->assignRole('superadmin');

        $this->command->info('Roles, permissions, and superadmin user created successfully!');
        $this->command->info('Superadmin credentials:');
        $this->command->info('Email: admin@commerce-saas.com');
        $this->command->info('Password: password');
    }
}
