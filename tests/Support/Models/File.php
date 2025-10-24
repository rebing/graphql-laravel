<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Rebing\GraphQL\Tests\Support\database\factories\FileFactory;

/**
 * @property int $id
 * @property string $name
 * @property string $path
 * @property int|null $folder_id
 * @property string|null $fileable_type
 * @property int|null $fileable_id
 */
class File extends Model
{
    use HasFactory;

    /** @var string[] */
    protected $guarded = [];

    protected static function newFactory(): Factory
    {
        return FileFactory::new();
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

}
