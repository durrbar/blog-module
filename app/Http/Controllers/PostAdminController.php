<?php

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        $cacheKey = self::CACHE_ADMIN_POSTS . $request->query('page', 1);
        $cacheDuration = now()->addMinutes(config('cache.duration'));

        $posts = Cache::remember($cacheKey, $cacheDuration, function () {
            return QueryBuilder::for(Post::class)
                ->allowedFields('id', 'slug', 'title', 'author_id', 'created_at', 'total_views', 'total_shares')
                ->with(['author', 'cover', 'tags'])
                ->allowedFilters([AllowedFilter::exact('publish')])
                ->allowedSorts('created_at')
                ->withCount(['comments' => fn($q) => $q->whereNull('parent_id')])
                ->paginate(10);
        });

        return response()->json(['posts' => new PostCollection($posts)]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PostRequest $request): JsonResponse
    {
        try {
            // Authorize the action using policies
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

            // Clear cache for all pages (or just the relevant one)
            $this->clearPostCache();

            return response()->json(['post' => new PostResource($post)], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->handleError(self::ERROR_CREATE . ': ' . $e->getMessage(), $request);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show(Post $post): JsonResponse
    {
        // Authorize the action using policies
        $this->authorize('view', $post);

        $cacheKey = "post_{$post->id}";
        $cacheDuration = now()->addMinutes(config('cache.duration'));

        $post = Cache::remember($cacheKey, $cacheDuration, function () use ($post) {
            return $this->loadPostRelations($post);
        });

        return response()->json(['post' => new PostResource($post)]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PostRequest $request, Post $post): JsonResponse
    {
        try {
            // Authorize the action using policies
            $this->authorize('update', $post);

            $post = DB::transaction(function () use ($request, $post) {
                $post->update($request->validated());

                $this->handleCoverImage($post, $request);

                return $this->loadPostRelations($post);
            });

            Cache::forget("post_{$post->id}");
            $this->clearPostCache();

            return response()->json(['post' => new PostResource($post)]);
        } catch (\Exception $e) {
            return $this->handleError(self::ERROR_UPDATE . ': ' . $e->getMessage(), $request);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post): JsonResponse
    {
        try {
            // Authorize the action using policies
            $this->authorize('delete', $post);

            DB::transaction(function () use ($post) {
                if ($post->cover && Storage::exists($post->cover->path)) {
                    Storage::delete($post->cover->path);
                    $post->cover->delete();
                }

                $post->delete();
            });

            Cache::forget("post_{$post->id}");
            $this->clearPostCache();

            return response()->json(['message' => 'Post deleted successfully.'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->handleError(self::ERROR_DELETE . ': ' . $e->getMessage(), null);
        }
    }
}
