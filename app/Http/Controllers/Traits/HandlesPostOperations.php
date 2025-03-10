<?php

namespace Modules\Blog\Http\Controllers\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Blog\Models\Post;
use Modules\Common\Facades\ErrorHelper;
use Modules\Common\Facades\FileHelper;

trait HandlesPostOperations
{
    protected const CACHE_PUBLIC_POSTS = 'api.v1.posts.public_';
    protected const CACHE_ADMIN_POSTS = 'api.v1.posts.admin_';
    protected const CACHE_FEATURED_POSTS = 'api.v1.posts.featured';
    protected const CACHE_LATEST_POSTS = 'api.v1.posts.latest';

    // Error messages
    protected const ERROR_CREATE = 'Failed to create post';
    protected const ERROR_UPDATE = 'Failed to update post';
    protected const ERROR_DELETE = 'Failed to delete post';
    protected const ERROR_FEATURED = 'Failed to retrieve featured posts';
    protected const ERROR_LATEST = 'Failed to retrieve latest posts';

    private function loadPostRelations(Post $post): Post
    {
        return $post->load(['author', 'cover', 'tags'])
            ->loadCount(['comments' => fn($q) => $q->whereNull('parent_id')]);
    }

    protected function handleCoverImage(Post $post, Request $request): void
    {
        if ($request->hasFile('coverUrl') || $request->input('coverUrl')) {
            $this->processCoverImage($post, $request);
        }
    }

    private function processCoverImage(Post $post, Request $request): void
    {
        // Get the existing cover path
        $existingCover = $post->cover?->path;

        // Get the incoming cover URL (string) or file (UploadedFile)
        $incomingCover = $request->input('coverUrl');
        $incomingFile = $request->file('coverUrl');

        // If the incoming cover is the same as the existing one, do nothing
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
                ->setPath('uploads/post/cover') // Set the specific path for product images
                ->generateUniqueFileName()
                ->setHeight(1080)
                ->upload()->getPath();

            // Only save the path in DB if upload is successful
            $post->cover()->updateOrCreate([], ['path' => $path]);
        }
    }

    protected function deleteCoverImage(Post $post): void
    {
        if ($post->cover && Storage::exists($post->cover->path)) {
            Storage::delete($post->cover->path);
            $post->cover->delete();
        }
    }

    /**
     * Clear the relevant post cache.
     */
    private function clearPostCache(): void
    {
        Cache::forget(self::CACHE_PUBLIC_POSTS . '*');
        Cache::forget(self::CACHE_ADMIN_POSTS . '*');
        Cache::forget(self::CACHE_FEATURED_POSTS);
        Cache::forget(self::CACHE_LATEST_POSTS);
    }

    /**
     * Handle error responses.
     *
     * @param string $message The error message to be logged and returned in the response.
     * @param Request|null $request The HTTP request that triggered the error, if available.
     * @param int $statusCode The HTTP status code for the response (default is 500).
     * @return JsonResponse A JSON response containing the success status and error message.
     */
    protected function handleError(string $message, ?Request $request = null, int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        // Use the ErrorHelper facade for error handling
        return ErrorHelper::handleError($message, $request, $statusCode);
    }
}
