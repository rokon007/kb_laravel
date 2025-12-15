<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->nullable()->constrained()->cascadeOnDelete();
            $table->text('content')->nullable();
            $table->enum('type', ['text', 'image', 'video', 'reel']);
            $table->string('media_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->integer('video_duration')->nullable();
            $table->enum('privacy', ['public', 'friends', 'private'])->default('public');
            $table->boolean('is_boosted')->default(false);
            $table->decimal('boost_budget', 10, 2)->default(0.00);
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->integer('shares_count')->default(0);
            $table->integer('views_count')->default(0);
            $table->enum('status', ['active', 'reported', 'removed'])->default('active');
            $table->timestamps();

            $table->index('user_id');
            $table->index('page_id');
            $table->index('type');
            $table->index('created_at');
            $table->index('status');
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
