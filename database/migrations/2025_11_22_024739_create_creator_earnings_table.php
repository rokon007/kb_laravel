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
        Schema::create('creator_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('advertisement_id')->nullable()->constrained('advertisements')->nullOnDelete();
            $table->enum('type', ['view', 'impression', 'engagement']);
            $table->decimal('amount', 10, 4);
            $table->decimal('admin_share', 10, 4);
            $table->decimal('creator_share', 10, 4);
            $table->timestamps();

            $table->index('user_id');
            $table->index('post_id');
            $table->index('created_at');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_earnings');
    }
};
