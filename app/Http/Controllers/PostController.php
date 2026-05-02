<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Modules\Blog\Enums\PostPublishStatus;
use Modules\Blog\Http\Controllers\Traits\HandlesPostOperations;
use Modules\Blog\Models\Post;
use Modules\Blog\Resources\PostCollection;
use Modules\Blog\Resources\PostJsonApiResource;
use Modules\Blog\Resources\PostResource;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\Searchable\ModelSearchAspect;
use Spatie\Searchable\Search;

class PostController extends Controller
{
    use HandlesPostOperations;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $cacheKey = self::CACHE_PUBLIC_POSTS.$request->integer('page', 1);
        $cacheDuration = now()->addMinutes(config('cache.duration'));

        $posts = Cache::remember($cacheKey, $cacheDuration, static fn () => QueryBuilder::for(Post::class)
            ->allowedFields('id', 'slug', 'title', 'author_id', 'created_at', 'total_views', 'total_shares')
            ->with(['author', 'cover'])
            ->where('publish', PostPublishStatus::Published->value)
            ->paginate(10));

        return PostJsonApiResource::collection($posts);
    }

    /**
     * Show the specified resource.
     */
    public function show(Post $post): JsonResponse
    {
        $cacheKey = "post_{$post->id}";
        $cacheDuration = now()->addMinutes(config('cache.duration'));

        $post = Cache::remember($cacheKey, $cacheDuration, fn () => $this->loadPostRelations($post));

        return response()->json(['post' => new PostResource($post)], Response::HTTP_OK);
    }

    public function featured(): JsonResponse
    {
        try {
            $featureds = Cache::remember(self::CACHE_FEATURED_POSTS, now()->addMinutes(config('cache.duration')), static fn () => Post::query()
                ->where('featured', true)
                ->select('id', 'slug', 'title', 'author_id', 'created_at', 'total_views', 'total_shares')
                ->with(['author', 'cover'])
                ->withCount(['comments' => fn (Builder $query) => $query->whereNull('parent_id')])
                ->limit(5)
                ->get());

            return response()->json(['featureds' => PostResource::collection($featureds)], Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->handleError(self::ERROR_FEATURED.': '.$e->getMessage(), null);
        }
    }

    public function latest(): JsonResponse
    {
        try {
            $latest = Cache::remember(self::CACHE_LATEST_POSTS, now()->addMinutes(config('cache.duration')), static fn () => Post::query()
                ->select(
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
                ->withCount(['comments' => fn (Builder $query) => $query->whereNull('parent_id')])
                ->latest('created_at')
                ->limit(5)
                ->get());

            return response()->json(['latest' => PostResource::collection($latest)], Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->handleError(self::ERROR_LATEST.': '.$e->getMessage(), null);
        }
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->string('query')->trim()->toString();

        if ($query === '') {
            return response()->json(['results' => []], Response::HTTP_OK);
        }

        $results = (new Search())
            ->registerModel(Post::class, static function (ModelSearchAspect $modelSearchAspect): void {
                $modelSearchAspect
                    ->addSearchableAttribute('title')
                    ->with('cover');
            })
            ->search($query);

        return response()->json([
            'results' => PostResource::collection(collect($results)->pluck('searchable')),
        ], Response::HTTP_OK);
    }
}
