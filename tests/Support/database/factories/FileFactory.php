<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Rebing\GraphQL\Tests\Support\Models\File;

class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word() . '.txt',
            'path' => '/files/' . $this->faker->word(),
            'folder_id' => null,
        ];
    }
}
