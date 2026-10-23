<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['name' => 'Nimal Perera',    'email' => 'nimal@example.com',   'phone' => '0771234567', 'address' => '12 Lake Road, Colombo'],
            ['name' => 'Kamala Silva',    'email' => 'kamala@example.com',  'phone' => '0712345678', 'address' => '45 Temple Street, Negombo'],
            ['name' => 'Sunil Fernando',  'email' => 'sunil@example.com',   'phone' => '0759876543', 'address' => '8 Beach Road, Galle'],
            ['name' => 'Dilani Jayasuriya','email' => 'dilani@example.com', 'phone' => '0765554433', 'address' => '23 Hill Street, Kandy'],
            ['name' => 'Ruwan Wickrama',  'email' => 'ruwan@example.com',   'phone' => '0701122334', 'address' => '67 Main Street, Kurunegala'],
        ];

        foreach ($customers as $customer) {
            Customer::updateOrCreate(['email' => $customer['email']], $customer);
        }
    }
}