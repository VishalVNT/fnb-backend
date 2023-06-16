<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\Company::factory(1000)->create();

        \App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'mobile' => '8108889047',
            'password' => '$2y$10$zNzc59OWiKQhlqtt8OEdKObYDWucH9m5av5ZNfuXDxXpDoKa1/q0O',
            'status' => 1,
        ]);
    }
}
