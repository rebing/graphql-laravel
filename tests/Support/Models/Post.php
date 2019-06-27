<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $title
 * @property string|null $body
 * @property int|null $user_id
 * @property bool $flag
 * @property-read \Illuminate\Database\Eloquent\Collection|Comment[] $comments
 */
class Post extends Model
{
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->orderBy('comments.id');
    }
}
