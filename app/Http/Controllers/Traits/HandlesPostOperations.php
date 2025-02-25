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

trait HandlesPostOperations
{
    protected const CACHE_PUBLIC_POSTS = 'api.v1.posts.public_';
    protected const CACHE_ADMIN_POSTS = 'api.v1.admin.posts_';
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
            $fileName = $this->generateUniqueFileName($incomingFile);
            $path = "uploads/post/cover/{$fileName}";

            if (extension_loaded('imagick')) {
                $this->storeResizedImage($incomingFile, $path);
            } else {
                $incomingFile->storeAs('uploads/post/cover', $fileName);
            }

            $post->cover()->updateOrCreate([], ['path' => $path]);
        }
    }

    private function storeResizedImage(UploadedFile $cover, string $path): void
    {
        // Ensure the directory exists
        $directory = dirname($path);
        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        $image = \Intervention\Image\Laravel\Facades\Image::make($cover->getPathname())
            ->resize(null, 300, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->encode($cover->getClientOriginalExtension(), 75);

        Storage::put($path, (string) $image);
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
     * Generate a unique filename for an uploaded image.
     *
     * @param UploadedFile $image The uploaded image file.
     * @return string A unique filename based on the original name and current timestamp.
     */
    protected function generateUniqueFileName(UploadedFile $image): string
    {
        $extension = $image->getClientOriginalExtension();
        $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        return uniqid($originalName . '_', true) . '.' . $extension;
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
        Log::error($message, [
            'request' => $request ? $request->all() : [],
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $message,
        ], $statusCode);
    }
}