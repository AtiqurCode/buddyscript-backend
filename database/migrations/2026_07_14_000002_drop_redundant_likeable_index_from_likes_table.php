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
        Schema::table('likes', function (Blueprint $table) {
            // morphs('likeable') auto-created this, but the unique index on
            // (likeable_type, likeable_id, user_id) already starts with the
            // same two columns and serves the same lookups — this one just
            // duplicates it and adds write overhead to every like/unlike.
            $table->dropIndex('likes_likeable_type_likeable_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('likes', function (Blueprint $table) {
            $table->index(['likeable_type', 'likeable_id'], 'likes_likeable_type_likeable_id_index');
        });
    }
};
