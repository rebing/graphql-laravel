<?php

declare(strict_types=1);

use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;
use Rebing\GraphQL\Tests\Support\Models\Tag;

/* @var Factory $factory */
$factory->define(Tag::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
    ];
});
