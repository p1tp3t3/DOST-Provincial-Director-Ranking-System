<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rename the second PSTD matrix category from FUNCTIONAL to STRATEGIC.
     * KPIs reference categories by category_id (FK), so updating the code/name
     * here is safe: all existing KPI links and director scores are untouched,
     * and RankingService keys its subtotals off this code.
     */
    public function up(): void
    {
        DB::table('kpi_categories')
            ->where('code', 'FUNCTIONAL')
            ->update(['code' => 'STRATEGIC', 'name' => 'Strategic']);
    }

    public function down(): void
    {
        DB::table('kpi_categories')
            ->where('code', 'STRATEGIC')
            ->update(['code' => 'FUNCTIONAL', 'name' => 'Functional']);
    }
};
