<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * S1: topics own lessons. Only the S1 slice fields are added here.
     * On PostgreSQL the enum columns compile to VARCHAR with CHECK
     * constraints (scope §14: allowed status set).
     */
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title_en');
            $table->text('summary_public');
            $table->enum('status', ['draft', 'published', 'archived']);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
