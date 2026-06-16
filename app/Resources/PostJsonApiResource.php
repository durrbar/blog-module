<?php

declare(strict_types=1);

namespace Modules\Blog\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Modules\Blog\Enums\PostPublishStatus;
use Modules\Comment\Http\Resources\CommentJsonApiResource;
use Modules\Tag\Resources\TagResource;
use Modules\User\Resources\UserJsonApiResource;

class PostJsonApiResource extends JsonApiResource
{
    /**
     * Get the resource's attributes.
     */
    public function toAttributes(Request $request): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'content' => $this->content,
            'featured' => (bool) $this->featured,
            'metaTitle' => $this->meta_title,
            'metaKeywords' => $this->meta_keywords,
            'metaDescription' => $this->meta_description,
            'totalViews' => (int) $this->total_views,
            'totalShares' => (int) $this->total_shares,
            'totalFavorites' => (int) $this->total_favorites,
            'publish' => $this->publish instanceof PostPublishStatus ? $this->publish->value : $this->publish,
            'duration' => $this->readTime,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
            'deletedAt' => $this->deleted_at?->toISOString(),
        ];
    }

    /**
     * The resource's relationships.
     */
    public function toRelationships(Request $request): array { 
        return [
            'author' => UserJsonApiResource::class,
            'comments' => CommentJsonApiResource::class,
            'tags' => TagResource::class,
            'cover',
        ];
    }

    /**
     * Get the resource's type.
     */
    public function toType(Request $request): string
    {
        return 'posts';
    }

    /**
     * Get the resource's links.
     */
    public function toLinks(Request $request): array
    {
        return [
            'self' => route('api.posts.show', $this->resource),
        ];
    }

    /**
     * Get the resource's meta information.
     */
    public function toMeta(Request $request): array
    {
        return [
        ];
    }
}
