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
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('media_type', ['image', 'video']);
            $table->string('media_url');
            $table->string('click_url');
            $table->enum('placement_type', ['feed', 'reel', 'video_preroll', 'video_midroll', 'sponsored']);
            $table->integer('target_age_min')->nullable();
            $table->integer('target_age_max')->nullable();
            $table->enum('target_gender', ['all', 'male', 'female', 'other'])->default('all');
            $table->json('target_locations')->nullable();
            $table->json('target_interests')->nullable();
            $table->decimal('budget', 10, 2);
            $table->decimal('spent', 10, 2)->default(0.00);
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected', 'running', 'paused', 'completed'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('placement_type');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
