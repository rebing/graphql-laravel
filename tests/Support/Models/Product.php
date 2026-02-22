<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Rebing\GraphQL\Tests\Support\database\factories\ProductFactory;

/**
 * @property int $id
 * @property string $name
 * @property float|null $price
 * @property Carbon|null $published_at
 * @property bool $is_published
 * @property-read File|null $file
 */
class Product extends Model
{
    use HasFactory;

    /** @var string[] */
    protected $dates = [
        'published_at',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    public function commentableComments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function getIsPublishedAttribute(): bool
    {
        $publishedAt = $this->published_at;

        return null !== $publishedAt;
    }

    protected static function newFactory(): Factory
    {
        return ProductFactory::new();
    }
}
