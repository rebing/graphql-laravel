<?php

declare(strict_types=1);

use Faker\Generator as Faker;
use Rebing\GraphQL\Tests\Support\Models\Post;

/* @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Post::class, function (Faker $faker) {
    return [
        'title' => $faker->title,
        'body' => $faker->sentence,
    ];
});
