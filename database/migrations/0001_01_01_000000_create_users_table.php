<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            
            // Laravel 12 এর ডিফল্ট columns
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            
            // নতুন columns (AFTER সরানো হলো)
            $table->string('username')->unique();
            $table->string('phone', 20)->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar')->nullable();
            $table->string('cover_photo')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('location')->nullable();
            
            // Social features
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_creator')->default(false);
            $table->boolean('is_admin')->default(false);
            $table->timestamp('creator_approved_at')->nullable();
            $table->boolean('is_banned')->default(false);
            $table->text('banned_reason')->nullable();
            
            // Monetization
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('total_earned', 15, 2)->default(0);
            
            // Counters
            $table->integer('followers_count')->default(0);
            $table->integer('following_count')->default(0);
            $table->integer('friends_count')->default(0);
            
            // Settings
            $table->json('privacy_settings')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('is_verified');
            $table->index('is_creator');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
