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
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('status')->default('draft'); // draft, published, archived
            $table->string('category')->nullable();
            $table->integer('views_count')->default(0);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            // Indexes for efficient filtering and sorting
            $table->index(['status', 'published_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('category');
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
