<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * S3: lesson content fields (scope §9). Glossary is validated JSON
     * (≤12 entries, enforced in App\Support\Glossary on every write);
     * reviewed_by/reviewed_at record the content review that publishing
     * requires. No progress, bookmarks, subscriptions, or payments here.
     */
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->jsonb('glossary')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['glossary', 'reviewed_at']);
        });
    }
};
