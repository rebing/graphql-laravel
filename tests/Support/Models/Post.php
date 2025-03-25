<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Rebing\GraphQL\Tests\Support\database\factories\PostFactory;

/**
 * @property int $id
 * @property string $title
 * @property string|null $body
 * @property int|null $user_id
 * @property array{name:?string,title:?string}|null $properties
 * @property bool $flag
 * @property Carbon|null $published_at
 * @property bool $is_published
 * @property-read Collection|Comment[] $comments
 * @property-read Collection|Like[] $likes
 */
class Post extends Model
{
    use HasFactory;

    /** @var string[] */
    protected $dates = [
        'published_at',
    ];

    /** @var array<string,string> */
    protected $casts = [
        'properties' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->orderBy('comments.id');
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likable');
    }

    public function getIsPublishedAttribute(): bool
    {
        $publishedAt = $this->published_at;

        return null !== $publishedAt;
    }

    protected static function newFactory(): Factory
    {
        return PostFactory::new();
    }
}
