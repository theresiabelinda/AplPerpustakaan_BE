<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Category; // HARUS diimpor
use App\Models\Book;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        // Pastikan Category ada sebelum dijalankan
        $category_id = Category::inRandomOrder()->first()->id ?? Category::factory();

        return [
            'title' => $this->faker->sentence(3),
            'author' => $this->faker->name(),
            'publisher' => $this->faker->company(),
            'category_id' => $category_id,
            'stock' => $this->faker->numberBetween(0, 10),
            'shelf_location' => $this->faker->randomLetter() . '-' . $this->faker->randomDigit(),
        ];
    }
}
