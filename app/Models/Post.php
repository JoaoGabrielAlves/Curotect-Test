<?php

namespace App\Models;

use Database\Factories\PostFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string $content
 * @property string $status
 * @property string|null $category
 * @property int $views_count
 * @property int $user_id
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Comment> $approvedComments
 * @property-read int|null $approved_comments_count
 * @property-read Collection<int, Comment> $comments
 * @property-read int|null $comments_count
 * @property-read mixed $e_tag
 * @property-read User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post byCategory($category)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post byStatus($status)
 * @method static \Database\Factories\PostFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post published()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereViewsCount($value)
 *
 * @mixin Eloquent
 */
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'content',
        'status',
        'category',
        'views_count',
        'user_id',
        'published_at',
    ];

    /**
     * Always appends e_tag custom attribute when getting posts
     *
     * @var string[]
     */
    protected $appends = ['e_tag'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'views_count' => 'integer',
        ];
    }

    /**
     * Get the user that owns the post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the comments for the post.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get approved comments for the post.
     */
    public function approvedComments(): HasMany
    {
        return $this->hasMany(Comment::class)->where('status', 'approved');
    }

    /**
     * Scope a query to only include published posts.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->when($category, fn ($q) => $q->where('category', $category));
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->when($status, fn ($q) => $q->where('status', $status));
    }

    /**
     * Generate ETag for concurrency control
     */
    protected function eTag(): Attribute
    {
        return Attribute::make(
            get: static function ($value, $attributes) {
                if (! isset($attributes['updated_at'])) {
                    return null;
                }

                return md5(new Carbon($attributes['updated_at'])->timestamp.$attributes['id']);
            }
        );
    }

    /**
     * Check if the provided ETag matches the current state.
     */
    public function isEtagValid(string $etag): bool
    {
        return $this->e_tag === $etag;
    }
}
