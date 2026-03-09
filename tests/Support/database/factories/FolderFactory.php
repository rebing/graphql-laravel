<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Rebing\GraphQL\Tests\Support\Models\Folder;

class FolderFactory extends Factory
{
    protected $model = Folder::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
        ];
    }
}
