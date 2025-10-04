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
        $admin = User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Admin User',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        // Create a default standard user
        $user = User::firstOrCreate([
            'email' => 'user@example.com',
        ], [
            'name' => 'Standard User',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('user');

        $this->call(SubscriptionPlansSeeder::class);
    }
}
