<?php

declare(strict_types=1);

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

    private function syncTags(Post $post): void
    {
        if (! request()->has('tags')) {
            return;
        }

        $tags = request()->input('tags', []);
        $post->syncTags($tags);
    }
}
