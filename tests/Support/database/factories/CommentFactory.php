<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Rebing\GraphQL\Tests\Support\Models\Comment;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'title' => fake()->title(),
            'body' => fake()->sentence(),
        ];
    }
}
