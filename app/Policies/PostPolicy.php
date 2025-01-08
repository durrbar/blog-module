<?php

namespace Modules\Blog\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Blog\Models\Post;
use Modules\User\Models\User;

class PostPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

        /**
     * Determine if the user can view any posts.
     */
    public function viewAny(User $user)
    {
        return $user->can('blog.posts.*') || $user->can('blog.posts.view');
    }

    /**
     * Determine if the user can view a specific post.
     */
    public function view(User $user, Post $post)
    {
        return $user->can('blog.posts.*') || $user->can('blog.posts.view');
    }

    /**
     * Determine if the user can create a post.
     */
    public function create(User $user)
    {
        return $user->can('blog.posts.*') || $user->can('blog.posts.create');
    }

    /**
     * Determine if the user can update the post.
     */
    public function update(User $user, Post $post)
    {
        return $user->can('blog.posts.*') || $user->can('blog.posts.edit') || $user->id === $post->user_id;
    }

    /**
     * Determine if the user can delete the post.
     */
    public function delete(User $user, Post $post)
    {
        return $user->can('blog.posts.*') || $user->can('blog.posts.delete') || $user->id === $post->user_id;
    }

    /**
     * Determine if the user can restore the post.
     */
    public function restore(User $user, Post $post)
    {
        return $user->can('blog.posts.*') || $user->can('blog.posts.update');
    }

    /**
     * Determine if the user can permanently delete the post.
     */
    public function forceDelete(User $user, Post $post)
    {
        return $user->can('blog.posts.*') && $user->hasRole(['Super Admin', 'Administrator']);
    }
}
