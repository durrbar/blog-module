<?php

declare(strict_types=1);

namespace Modules\Blog\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\User\Resources\UserResource;

class CommonPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'authorId' => $this->author_id,
            'createdAt' => $this->created_at,
            'totalViews' => $this->total_views,
            'totalShares' => $this->total_shares,
            'totalComments' => $this->whenCounted('comments'),
            'coverUrl' => $this->whenLoaded('cover', fn () => $this->cover?->url, config('media.defaults.post_cover')),
            'author' => new UserResource($this->whenLoaded('author')),
        ];
    }
}
