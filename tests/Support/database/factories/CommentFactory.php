<?php

declare(strict_types=1);

use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;
use Rebing\GraphQL\Tests\Support\Models\Comment;

/* @var Factory $factory */
$factory->define(Comment::class, function (Faker $faker) {
    return [
        'title' => $faker->title,
        'body' => $faker->sentence,
    ];
});
