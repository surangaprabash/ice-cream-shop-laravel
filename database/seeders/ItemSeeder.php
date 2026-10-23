<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'Chocolate' => [
                ['Dark Chocolate Tub',    'Intense dark chocolate, 1L tub',  1200.00, 25, 'tub'],
                ['Chocolate Cone',        'Single scoop chocolate cone',      350.00, 80, 'cone'],
                ['Chocolate Fudge Cup',   'Chocolate with fudge sauce',       450.00, 40, 'cup'],
            ],
            'Vanilla' => [
                ['Vanilla Bean Tub',      'Classic vanilla bean, 1L tub',    1100.00, 30, 'tub'],
                ['Vanilla Cone',          'Single scoop vanilla cone',        320.00, 90, 'cone'],
                ['French Vanilla Cup',    'Creamy French vanilla cup',        420.00, 35, 'cup'],
            ],
            'Strawberry' => [
                ['Strawberry Tub',        'Real strawberry, 1L tub',         1150.00, 28, 'tub'],
                ['Strawberry Cone',       'Single scoop strawberry cone',     340.00, 70, 'cone'],
                ['Strawberry Swirl Cup',  'Strawberry with fruit swirl',      430.00, 32, 'cup'],
            ],
            'Fruit and Nuts' => [
                ['Fruit and Nut Tub',     'Mixed fruits and nuts, 1L tub',   1350.00, 20, 'tub'],
                ['Fruit and Nut Cup',     'Mixed fruits and nuts cup',        480.00, 30, 'cup'],
                ['Cashew Delight Cone',   'Cashew and dried fruit cone',      400.00, 45, 'cone'],
            ],
            'Rocky Road' => [
                ['Rocky Road Tub',        'Chocolate, marshmallow and nuts, 1L tub', 1400.00, 22, 'tub'],
                ['Rocky Road Cone',       'Single scoop rocky road cone',     420.00, 55, 'cone'],
                ['Rocky Road Sundae',     'Rocky road with toppings',         550.00, 25, 'sundae'],
            ],
            'Cookies and Cream' => [
                ['Cookies and Cream Tub', 'Oreo-style cookie pieces, 1L tub', 1300.00, 24, 'tub'],
                ['Cookies and Cream Cone','Single scoop cookies and cream',   380.00, 60, 'cone'],
                ['Cookie Crumble Sundae', 'Cookies and cream sundae',         520.00, 28, 'sundae'],
            ],
        ];

        foreach ($items as $categoryName => $categoryItems) {
            $category = Category::where('name', $categoryName)->firstOrFail();

            foreach ($categoryItems as [$name, $description, $price, $quantity, $unit]) {
                Item::updateOrCreate(
                    ['name' => $name, 'category_id' => $category->id],
                    [
                        'description' => $description,
                        'price' => $price,
                        'quantity' => $quantity,
                        'unit' => $unit,
                    ]
                );
            }
        }
    }
}