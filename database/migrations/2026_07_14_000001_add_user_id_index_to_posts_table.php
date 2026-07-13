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
        Schema::table('posts', function (Blueprint $table) {
            // A foreign key alone doesn't get an index on every driver
            // (SQLite doesn't auto-index FK columns the way MySQL does),
            // and this one is queried directly — the "my own private
            // posts" branch of Post::scopeVisibleTo() filters on it.
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });
    }
};
