<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Rebing\GraphQL\Tests\Support\Models\Like;

/**
 * @extends Factory<Like>
 */
class LikeFactory extends Factory
{
    protected $model = Like::class;

    public function definition(): array
    {
        return [
        ];
    }
}
