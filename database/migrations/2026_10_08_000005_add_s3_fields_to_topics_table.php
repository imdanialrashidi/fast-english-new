<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * S3: topic content fields (scope §9). Category is optional;
     * cover_path lives on the public disk; source_note is private and
     * never rendered to learners.
     */
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('cover_path')->nullable();
            $table->text('source_note')->nullable();
        });

        Schema::table('topics', function (Blueprint $table) {
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn(['cover_path', 'source_note']);
        });
    }
};
