<?php

declare(strict_types=1);

namespace Modules\Blog\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Blog\Enums\PostPublishStatus;
use Modules\User\Resources\UserResource;

class PostSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'publish' => $this->publish instanceof PostPublishStatus ? $this->publish->value : $this->publish,
            'featured' => (bool) $this->featured,
            'duration' => $this->readTime,
            'coverUrl' => $this->whenLoaded('cover', fn () => $this->cover?->url),
            'author' => new UserResource($this->whenLoaded('author')),
            'totalViews' => (int) $this->total_views,
            'totalShares' => (int) $this->total_shares,
            'totalFavorites' => (int) $this->total_favorites,
            'totalComments' => $this->whenCounted('comments'),
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
