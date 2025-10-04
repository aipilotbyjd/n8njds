<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run the roles and permissions seeder
        $this->call(RolesAndPermissionsSeeder::class);

        // Create a default admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);
        $admin->assignRole('admin');

        // Create a default standard user
        $user = User::factory()->create([
            'name' => 'Standard User',
            'email' => 'user@example.com',
        ]);
        $user->assignRole('user');
    }
}
