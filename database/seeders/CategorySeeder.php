<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Tents & Shelters', 'slug' => 'tents-shelters'],
            ['name' => 'Backpacks', 'slug' => 'backpacks'],
            ['name' => 'Sleeping Gear', 'slug' => 'sleeping-gear'],
            ['name' => 'Cooking', 'slug' => 'cooking'],
            ['name' => 'Hardware', 'slug' => 'hardware'],
            ['name' => 'Lighting', 'slug' => 'lighting'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
