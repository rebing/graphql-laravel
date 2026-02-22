<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Rebing\GraphQL\Tests\Support\database\factories\FolderFactory;

/**
 * @property int $id
 * @property string $name
 */
class Folder extends Model
{
    use HasFactory;

    /** @var string[] */
    protected $guarded = [];

    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    protected static function newFactory(): Factory
    {
        return FolderFactory::new();
    }
}
