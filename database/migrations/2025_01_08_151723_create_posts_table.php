<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('title');
            $table->string('slug');
            $table->string('publish')->default('draft');
            $table->boolean('featured')->default(false);
            $table->longText('content');
            $table->foreignUuid('author_id')->constrained('users')->onDelete('cascade');
            $table->text('description');
            $table->integer('total_views')->default(0);
            $table->integer('total_shares')->default(0);
            $table->integer('total_favorites')->default(0);

            $table->string('meta_title');
            $table->json('meta_keywords');
            $table->string('meta_description');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
