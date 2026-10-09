<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * S3: new content starts as a draft (scope §9.3 lifecycle). The Filament
     * forms carry no status field on purpose — publishing is an explicit
     * action — so the database owns the draft default for every writer.
     */
    public function up(): void
    {
        DB::statement("alter table topics alter column status set default 'draft'");
        DB::statement("alter table lessons alter column status set default 'draft'");
    }

    public function down(): void
    {
        DB::statement('alter table topics alter column status drop default');
        DB::statement('alter table lessons alter column status drop default');
    }
};
