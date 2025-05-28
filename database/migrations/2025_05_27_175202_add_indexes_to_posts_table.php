<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Check if indexes don't already exist before creating them

            // Composite index for status + created_at (common combination)
            $table->index(['status', 'created_at'], 'posts_status_created_at_index');

            // Index for views_count sorting
            $table->index('views_count', 'posts_views_count_index');

            // Index for published_at sorting
            $table->index('published_at', 'posts_published_at_index');

            // Composite index for trending posts query
            $table->index(['status', 'created_at', 'views_count'], 'posts_trending_index');
        });

        // Full-text search index for PostgreSQL
        if (config('database.default') === 'pgsql') {
            DB::statement('CREATE INDEX IF NOT EXISTS posts_search_idx ON posts USING gin(to_tsvector(\'english\', title || \' \' || content))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_status_created_at_index');
            $table->dropIndex('posts_views_count_index');
            $table->dropIndex('posts_published_at_index');
            $table->dropIndex('posts_trending_index');
        });

        // Drop full-text search index for PostgreSQL
        if (config('database.default') === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS posts_search_idx');
        }
    }
};
