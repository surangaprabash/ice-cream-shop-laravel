<?php

namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Enums\Role;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Shop Owner',   'email' => 'owner@icecream.test',   'role' => Role::Owner],
            ['name' => 'Shop Manager', 'email' => 'manager@icecream.test', 'role' => Role::Manager],
            ['name' => 'Shop Cashier', 'email' => 'cashier@icecream.test', 'role' => Role::Cashier],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => 'password123', // hashed automatically when cast by model
                    'role' => $user['role'],
                ]
            );
        }
    }
}