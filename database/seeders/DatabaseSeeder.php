<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $demo = User::factory()->create([
            'name' => 'Demo',
            'email' => 'demo@example.com',
            'password' => 'password',
        ]);

        Task::factory()->count(8)->for($demo)->create();
    }
}
