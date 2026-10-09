<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * R5 daily learning path (PLAN-01): explicit daily goal in minutes.
     * Only 5, 10, or 15 are valid; the default is 10. Changing the goal
     * never touches preferred_level (separate explicit action).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->smallInteger('daily_goal_minutes')->default(10)->after('preferred_level');
        });

        DB::statement('alter table users add constraint users_daily_goal_allowed check (daily_goal_minutes in (5, 10, 15))');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('daily_goal_minutes');
        });
    }
};
