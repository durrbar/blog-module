<?php

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Tags\Tag as TagsTag;

class Tag extends TagsTag
{
    use HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'tags';

    /**
     * Get all of the posts that are assigned this tag.
     */
    public function posts(): MorphToMany
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }
}
