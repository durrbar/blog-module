<?php

declare(strict_types=1);

namespace Modules\Blog\Models;

use App\Models\Image;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Leshkens\LaravelReadTime\Traits\HasReadTime;
use Modules\Blog\Enums\PostPublishStatus;
use Modules\Blog\Policies\PostPolicy;
use Modules\Comment\Models\Comment;
use Modules\User\Models\User;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Tags\HasTags;

// use Modules\Blog\Database\Factories\PostFactory;

#[Table('posts')]
#[Fillable([
    'title',
    'publish',
    'content',
    'cover_url',
    'author_id',
    'meta_title',
    'total_views',
    'description',
    'total_shares',
    'meta_keywords',
    'total_favorites',
    'meta_description',
])]
#[UsePolicy(PostPolicy::class)]
class Post extends Model implements Searchable
{
    use HasFactory;
    use HasReadTime;
    use HasSlug;
    use HasTags;
    use HasUuids;
    use SoftDeletes;

    public static function getTagClassName(): string
    {
        return Tag::class;
    }

    public function getSearchResult(): SearchResult
    {
        $url = route('api.posts.show', $this->slug);

        return new SearchResult(
            $this,
            $this->title,
            $url
        );
    }

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(50);
    }

    /**
     * Get the author that owns the post.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Return the comments relationship.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Return the comments relationship.
     */
    public function cover(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    /**
     * Return the tags relationship.
     */
    public function tags(): MorphToMany
    {
        return $this
            ->morphToMany(self::getTagClassName(), 'taggable', 'taggables', null, 'tag_id')
            ->orderBy('order_column');
    }

    /**
     * The table associated with the model.
     */

    // protected static function newFactory(): PostFactory
    // {
    //     // return PostFactory::new();
    // }
    
    protected function casts(): array
    {
        return [
            'publish' => PostPublishStatus::class,
            'meta_keywords' => 'array',
        ];
    }

    protected function readTime(): array
    {
        return [
            'source' => 'content',

            'localable' => true,
        ];
    }
}
