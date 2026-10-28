<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach ([
            ['Thomas', 'thomas@example.com'],
            ['Priya Sharma', 'priya@example.com'],
            ['Arjun Mehta', 'arjun@example.com'],
            ['Fatima Khan', 'fatima@example.com'],
        ] as [$name, $email]) {
            Customer::create(['name' => $name, 'email' => $email]);
        }

        Customer::factory(6)->create();
    }
}
