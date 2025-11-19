<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['Fiksi', 'Pendidikan', 'Sains', 'Sejarah', 'Komputer', 'Bisnis'];
        foreach ($categories as $cat) {
            Category::create(['name' => $cat]);
        }
    }
}
