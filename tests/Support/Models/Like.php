<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Rebing\GraphQL\Tests\Support\database\factories\LikeFactory;

/**
 * @property int $id
 * @property int $user_id
 * @property string $likable_type
 * @property int $likable_id
 */
class Like extends Model
{
    use HasFactory;

    /** @var string[] */
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function likable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): Factory
    {
        return LikeFactory::new();
    }
}
