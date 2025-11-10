<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Rebing\GraphQL\Tests\Support\Models\Product;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'price' => fake()->randomFloat(2, 10, 1000),
        ];
    }
}
