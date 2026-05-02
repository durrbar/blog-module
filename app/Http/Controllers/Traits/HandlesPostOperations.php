<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Modules\Blog\Models\Post;
use Modules\Core\Facades\ErrorHelper;
use Modules\Core\Facades\FileHelper;

trait HandlesPostOperations
{
    protected const CACHE_PUBLIC_POSTS = 'api.v1.posts.public_';

    protected const CACHE_ADMIN_POSTS = 'api.v1.posts.admin_';

    protected const CACHE_FEATURED_POSTS = 'api.v1.posts.featured';

    protected const CACHE_LATEST_POSTS = 'api.v1.posts.latest';

    protected const ERROR_CREATE = 'Failed to create post';

    protected const ERROR_UPDATE = 'Failed to update post';

    protected const ERROR_DELETE = 'Failed to delete post';

    protected const ERROR_FEATURED = 'Failed to retrieve featured posts';

    protected const ERROR_LATEST = 'Failed to retrieve latest posts';

    protected function handleCoverImage(Post $post, Request $request): void
    {
        if ($request->hasFile('coverUrl') || $request->filled('coverUrl')) {
            $this->processCoverImage($post, $request);
        }
    }

    protected function deleteCoverImage(Post $post): void
    {
        if ($post->cover && Storage::exists($post->cover->path)) {
            Storage::delete($post->cover->path);
            $post->cover->delete();
        }
    }

    protected function handleError(string $message, ?Request $request = null, int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        return ErrorHelper::handleError($message, $request, $statusCode);
    }

    protected function loadPostRelations(Post $post): Post
    {
        return $post->load(['author', 'cover', 'tags'])
            ->loadCount(['comments' => fn (Builder $query) => $query->whereNull('parent_id')]);
    }

    protected function processCoverImage(Post $post, Request $request): void
    {
        $existingCover = $post->cover?->path;
        $incomingCover = $request->input('coverUrl');
        $incomingFile = $request->file('coverUrl');

        if (is_string($incomingCover) && $incomingCover === $existingCover) {
            return;
        }

        if ($existingCover && Storage::exists($existingCover)) {
            Storage::delete($existingCover);
        }

        if (is_string($incomingCover)) {
            $post->cover()->updateOrCreate([], ['path' => $incomingCover]);

            return;
        }

        if ($incomingFile instanceof UploadedFile) {
            $path = FileHelper::setFile($incomingFile)
                ->setPath('uploads/post/cover')
                ->generateUniqueFileName()
                ->setHeight(1080)
                ->upload()->getPath();

            $post->cover()->updateOrCreate([], ['path' => $path]);
        }
    }

    protected function clearPostCache(): void
    {
        Cache::forget(self::CACHE_PUBLIC_POSTS.'*');
        Cache::forget(self::CACHE_ADMIN_POSTS.'*');
        Cache::forget(self::CACHE_FEATURED_POSTS);
        Cache::forget(self::CACHE_LATEST_POSTS);
    }
}
