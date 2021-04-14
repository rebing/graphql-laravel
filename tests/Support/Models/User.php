<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property-read Collection|Post[] $posts
 * @property-read Collection|Like[] $likes
 */
class User extends Model
{
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class)->orderBy('posts.id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }
}
