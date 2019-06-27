<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $post_id
 * @property string $title
 * @property string|null $body
 * @property bool $flag
 */
class Comment extends Model
{
}
