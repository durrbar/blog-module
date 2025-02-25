<?php

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Modules\Blog\Http\Controllers\Traits\HandlesPostOperations;
use Modules\Blog\Models\Post;
use Modules\Blog\Resources\PostCollection;
use Modules\Blog\Resources\PostResource;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\Searchable\ModelSearchAspect;
use Spatie\Searchable\Search;

class PostController extends Controller
{
    use AuthorizesRequests;
    use HandlesPostOperations;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $cacheKey = self::CACHE_PUBLIC_POSTS . $request->query('page', 1);
        $cacheDuration = now()->addMinutes(config('cache.duration'));

        $posts = Cache::remember($cacheKey, $cacheDuration, function () {
            return QueryBuilder::for(Post::class)
                ->allowedFields('id', 'slug', 'title', 'author_id', 'created_at', 'total_views', 'total_shares')
                ->with(['author', 'cover'])->where('publish', 'published')->paginate(10);
        });

        return response()->json(['posts' => new PostCollection($posts)]);
    }

    /**
     * Show the specified resource.
     */
    public function show(Post $post): JsonResponse
    {
        $cacheKey = "post_{$post->id}";
        $cacheDuration = now()->addMinutes(config('cache.duration'));

        $post = Cache::remember($cacheKey, $cacheDuration, function () use ($post) {
            return $this->loadPostRelations($post);
        });

        return response()->json(['post' => new PostResource($post)]);
    }

    public function featured(): JsonResponse
    {
        try {
            $featureds = Cache::remember(self::CACHE_FEATURED_POSTS, now()->addMinutes(config('cache.duration')), function () {
                return Post::where('featured', 1)
                    ->select('id', 'slug', 'title', 'author_id', 'created_at', 'total_views', 'total_shares')
                    ->with(['author', 'cover'])
                    ->withCount(['comments' => function ($query) {
                        $query->whereNull('parent_id');
                    }])
                    ->limit(5)
                    ->get();
            });

            return response()->json(['featureds' => PostResource::collection($featureds)], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->handleError(self::ERROR_FEATURED . ': ' . $e->getMessage(), null);
        }
    }

    public function latest(): JsonResponse
    {
        try {
            $latest = Cache::remember(self::CACHE_LATEST_POSTS, now()->addMinutes(config('cache.duration')), function () {
                return Post::select(
                    'id',
                    'slug',
                    'title',
                    'content',
                    'author_id',
                    'created_at',
                    'total_views',
                    'total_shares',
                    'description'
                )
                    ->with(['author', 'cover'])
                    ->withCount(['comments' => function ($query) {
                        $query->whereNull('parent_id');
                    }])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
            });

            return response()->json(['latest' => PostResource::collection($latest)], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->handleError(self::ERROR_LATEST . ': ' . $e->getMessage(), null);
        }
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->query('query');

        // Perform the search and register the Post model with searchable attributes
        $results = (new Search())
            ->registerModel(Post::class, function (ModelSearchAspect $modelSearchAspect) {
                $modelSearchAspect
                    ->addSearchableAttribute('title')
                    ->with('cover'); // Eager load the cover relationship
            })
            ->search($query);

        // Use PostResource to format the search results consistently
        return response()->json(['results' => PostResource::collection(collect($results)->pluck('searchable'))]);
    }
}
