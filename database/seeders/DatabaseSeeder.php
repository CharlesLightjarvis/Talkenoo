<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            [
                'fullName' => 'Charles Light Jarvis',
                'email' => 'charlestagne55@gmail.com',
                'phone' => '+21628509092',
                'password' => Hash::make('password'),
            ],
            [
                'fullName' => 'Lionel Yashiro',
                'email' => 'lionelyashiro@gmail.com',
                'phone' => '+3345457895',
                'password' => Hash::make('password'),
            ],
        ];
        // User::factory(10)->create();
        foreach ($users as $user) {
            User::create($user);
        }
    }
}
