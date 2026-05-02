<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Blog\Http\Controllers\Traits\HandlesPostOperations;
use Modules\Blog\Http\Requests\PostRequest;
use Modules\Blog\Models\Post;
use Modules\Blog\Resources\PostCollection;
use Modules\Blog\Resources\PostResource;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PostAdminController extends Controller
{
    use AuthorizesRequests;
    use HandlesPostOperations;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $cacheKey = self::CACHE_ADMIN_POSTS.$request->integer('page', 1);
        $cacheDuration = now()->addMinutes(config('cache.duration'));

        $posts = Cache::remember($cacheKey, $cacheDuration, static fn () => QueryBuilder::for(Post::class)
            ->allowedFields(['id', 'slug', 'title', 'author_id', 'created_at', 'total_views', 'total_shares'])
            ->with(['author', 'cover', 'tags'])
            ->allowedFilters([AllowedFilter::exact('publish')])
            ->allowedSorts('created_at')
            ->withCount(['comments' => fn (Builder $query) => $query->whereNull('parent_id')])
            ->paginate(10));

        return response()->json(['posts' => new PostCollection($posts)], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PostRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', Post::class);

            $post = DB::transaction(function () use ($request) {
                $post = Post::create([
                    ...$request->validated(),
                    'author_id' => Auth::id(),
                    'total_views' => 0,
                    'total_shares' => 0,
                    'total_favorites' => 0,
                ]);

                $this->handleCoverImage($post, $request);

                return $post;
            });

            $this->clearPostCache();

            return response()->json(['post' => new PostResource($post)], Response::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->handleError(self::ERROR_CREATE.': '.$e->getMessage(), $request);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show(Post $post): JsonResponse
    {
        $this->authorize('view', $post);

        $cacheKey = "post_{$post->id}";
        $cacheDuration = now()->addMinutes(config('cache.duration'));

        $post = Cache::remember($cacheKey, $cacheDuration, fn () => $this->loadPostRelations($post));

        return response()->json(['post' => new PostResource($post)], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PostRequest $request, Post $post): JsonResponse
    {
        try {
            $this->authorize('update', $post);

            $post = DB::transaction(function () use ($request, $post) {
                $post->update($request->validated());

                $this->handleCoverImage($post, $request);

                return $this->loadPostRelations($post);
            });

            Cache::forget("post_{$post->id}");
            $this->clearPostCache();

            return response()->json(['post' => new PostResource($post)], Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->handleError(self::ERROR_UPDATE.': '.$e->getMessage(), $request);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post): JsonResponse
    {
        try {
            $this->authorize('delete', $post);

            DB::transaction(function () use ($post): void {
                $this->deleteCoverImage($post);
                $post->delete();
            });

            Cache::forget("post_{$post->id}");
            $this->clearPostCache();

            return response()->json(['message' => 'Post deleted successfully.'], Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->handleError(self::ERROR_DELETE.': '.$e->getMessage(), null);
        }
    }
}
