<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SubscriptionPlan::create([
            'name' => 'Free',
            'slug' => 'free',
            'stripe_plan_id' => 'price_12345',
            'price' => 0,
            'features' => ['100 executions', '1 user'],
        ]);

        SubscriptionPlan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'stripe_plan_id' => 'price_67890',
            'price' => 2900,
            'features' => ['10000 executions', '5 users', 'Premium support'],
        ]);

        SubscriptionPlan::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'stripe_plan_id' => 'price_abcde',
            'price' => 9900,
            'features' => ['Unlimited executions', 'Unlimited users', 'Dedicated support', 'White-labeling'],
        ]);
    }
}
