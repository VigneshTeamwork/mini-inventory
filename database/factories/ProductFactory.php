<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('???-####')),
            'name' => ucfirst(fake()->words(2, true)),
            'price' => fake()->randomFloat(2, 5, 500),
            'tax_percent' => fake()->randomElement([0, 5, 12, 18]),
            'stock_on_hand' => fake()->numberBetween(20, 200),
        ];
    }

    public function lowStock(int $units = 3): static
    {
        return $this->state(fn () => ['stock_on_hand' => $units]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock_on_hand' => 0]);
    }
}
