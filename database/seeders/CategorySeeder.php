<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Chocolate',        'description' => 'Rich and creamy chocolate ice cream.'],
            ['name' => 'Vanilla',          'description' => 'Classic smooth vanilla ice cream.'],
            ['name' => 'Strawberry',       'description' => 'Fresh strawberry ice cream.'],
            ['name' => 'Fruit and Nuts',   'description' => 'Ice cream with mixed fruits and crunchy nuts.'],
            ['name' => 'Rocky Road',       'description' => 'Chocolate with marshmallows and nuts.'],
            ['name' => 'Cookies and Cream','description' => 'Vanilla ice cream with Oreo-style cookie pieces.'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['name' => $category['name']], $category);
        }
    }
}