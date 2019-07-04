<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property string $title
 * @property string|null $body
 * @property int|null $user_id
 * @property bool $flag
 * @property-read \Illuminate\Database\Eloquent\Collection|Comment[] $comments
 * @property-read \Illuminate\Database\Eloquent\Collection|Like[] $likes
 */
class Post extends Model
{
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
}
