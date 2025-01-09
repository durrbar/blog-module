<?php

namespace Modules\Blog\Observers;

use Modules\Blog\Models\Post;

class PostTagSyncObserver
{
    public function created(Post $post): void
    {
        $this->syncTags($post);
    }

    public function updating(Post $post): void
    {
        $this->syncTags($post);
    }

    private function syncTags(Post $post)
    {
        $tags = request()->input('tags', []); // Access tags from request
        $post->syncTags($tags);
    }
}
