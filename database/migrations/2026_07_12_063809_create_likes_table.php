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
        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Polymorphic target — a Post or a Comment (replies are
            // comments too), so posts, comments and replies all reuse the
            // same table and the same User::morphedByMany() relationship
            // instead of three separate like systems.
            $table->morphs('likeable');

            $table->timestamps();

            // One like per user per thing, and this doubles as the index
            // that powers both "does user X like this" checks and
            // "how many likes does this have" counts.
            $table->unique(['likeable_type', 'likeable_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};
