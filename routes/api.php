<?php

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\PostAdminController;
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

Route::prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum'])->name('dashboard.')->prefix('dashboard')->group(function () {
        Route::apiResource('posts', PostAdminController::class)->withTrashed()->names('posts');

        Route::get('tag', fn() => ['tags' => TagResource::collection(Tag::all())]);
    });

    Route::controller(PostController::class)->name('posts.')->prefix('posts')->group(function () {

        Route::get('featureds', 'featured')->name('featured');

        Route::get('latest', 'latest')->name('latest');

        Route::get('search', 'search')->name('search');
    });

    Route::apiResource('posts', PostController::class)->only(['index', 'show'])->scoped(['post' => 'slug']);
});
