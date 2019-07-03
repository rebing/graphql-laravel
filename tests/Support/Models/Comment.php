<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property int $post_id
 * @property string $title
 * @property string|null $body
 * @property bool $flag
 * @property-read \Illuminate\Database\Eloquent\Collection|Like[] $likes
 */
class Comment extends Model
{
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likable');
    }
}
