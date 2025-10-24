<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Rebing\GraphQL\Tests\Support\database\factories\CommentFactory;

/**
 * @property int                    $id
 * @property int                    $post_id
 * @property string                 $title
 * @property string|null            $body
 * @property bool                   $flag
 * @property-read Post              $post
 * @property-read Collection|Like[] $likes
 */
class Comment extends Model
{
    use HasFactory;

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likable');
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): Factory
    {
        return CommentFactory::new();
    }
}
