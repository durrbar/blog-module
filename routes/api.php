<?php

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\PostController;
use Modules\Tag\Models\Tag;
use Modules\Tag\Resources\TagResource;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('blog', PostController::class)->withTrashed()->names('posts');

    Route::get('tag', fn() => ['tags' => TagResource::collection(Tag::all())]);
});

Route::prefix('posts')->name('posts.')->controller(PostController::class)->group(function () {
    Route::get('featureds', 'featured')->name('featured');
    Route::get('latest', 'latest')->name('latest');
    Route::get('search', 'search')->name('search');
});
