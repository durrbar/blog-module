<?php

declare(strict_types=1);

namespace Modules\Blog\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Blog\Models\Post;
use Modules\Comment\Models\Comment;
use Modules\User\Models\User;

class BlogDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $this->call([]);

        $users = User::factory()->count(20)->create();

        // Build 12 Posts
        foreach (range(0, 11) as $index) {
            $isPublished = ($index % 3 !== 0); // index % 3 ? 'published' : 'draft'

            $post = Post::factory()
                ->create([
                    'author_id' => $users->random()->id,
                    'publish' => $isPublished ? 'published' : 'draft',
                ]);

            // Attach 24 top-level comments
            Comment::factory()
                ->count(24)
                ->create([
                    'commentable_id' => $post->id,
                    'commentable_type' => Post::class,
                    'author_id' => fn () => $users->random()->id,
                ])
                ->each(function ($comment) use ($users, $post) {
                    if (rand(1, 3) > 1) { // Implement  nested reply probability criteria: (index % 3)
                        $replyCount = rand(1, 5);

                        Comment::factory()
                            ->count($replyCount)
                            ->create([
                                'commentable_id' => $post->id,
                                'commentable_type' => Post::class,
                                'parent_id' => $comment->id,
                                'author_id' => fn () => $users->random()->id,
                                'content' => 'Replied to thread: '.fake()->sentence(5),
                            ]);
                    }
                });
        }
    }
}
