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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            // Always the top-level post, even for a reply — lets a whole
            // thread load in one query instead of walking parent_id up
            // through the comments table.
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();

            // Null for a top-level comment, set for a reply. Replies and
            // comments are the same model/table — a reply is just a
            // comment with a parent, so likes/authorship/etc. aren't
            // duplicated across two near-identical tables.
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            // Covers "top-level comments for post X" (parent_id IS NULL)
            // and "replies for comment Y" in created order.
            $table->index(['post_id', 'parent_id', 'created_at']);

            // "replies for comment Y" (GET /comments/{comment}/replies) filters
            // by parent_id alone, not post_id — the index above can't serve
            // that since post_id is its leading column. This one covers it
            // directly, id-ordered so the oldest-first reply listing doesn't
            // need a separate filesort.
            $table->index(['parent_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
