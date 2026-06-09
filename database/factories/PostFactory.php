<?php

declare(strict_types=1);

namespace Modules\Blog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Blog\Models\Post;
use Modules\User\Models\User;

#[UseModel(Post::class)]
class PostFactory extends Factory
{
    public function definition(): array
    {
        $title = $this->faker->sentence(6);

        // Exactly mirrors your TypeScript mock HTML block content
        $contentHTML = '
        <h1 class="nml__editor__content__heading">Heading H1</h1>
        <p>'.$this->faker->paragraph.'</p>
        <blockquote class="nml__editor__content__blockquote"><p>Life is short, Smile while you still have teeth!&nbsp;</p></blockquote>
        <pre class="nml__editor__content__code__block"><code class="language-javascript">for (var i=1; i <= 20; i++) {\n  if (i % 15 == 0)\n    return "FizzBuzz"\n}</code></pre>
        <img class="nml__editor__content__image" src="/assets/images/cover/cover-5.webp">
        <p>'.$this->faker->paragraph.'</p>';

        return [
            'id' => (string) Str::uuid(),
            'title' => $title,
            'slug' => Str::slug($title),
            'publish' => $this->faker->randomElement(['published', 'draft']),
            'featured' => $this->faker->boolean(20), // 20% chance to be featured
            'content' => $contentHTML,
            'author_id' => User::factory(), // Generates an author if none is provided
            'description' => $this->faker->sentence(12),
            'total_views' => $this->faker->numberBetween(100, 5000),
            'total_shares' => $this->faker->numberBetween(10, 2000),
            'total_favorites' => $this->faker->numberBetween(5, 1500),
            'meta_title' => $title,
            'meta_keywords' => ['Marketing', 'Development', 'Design'], // Casts perfectly to JSON in Eloquent
            'meta_description' => $this->faker->sentence(10),
        ];
    }

    // State states to enforce rules programmatically like "index % 3"
    public function draft(): self
    {
        return $this->state(fn (array $attributes) => ['publish' => 'draft']);
    }

    public function published(): self
    {
        return $this->state(fn (array $attributes) => ['publish' => 'published']);
    }
}
