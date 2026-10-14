<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// What an approved correction was used for: when the province last saved
// changes in the reopened period, and — when that year's rankings were already
// released at the time — when it revised them. `history` keeps every step
// (requested, returned, resubmitted, forwarded, approved, declined) with who,
// when and their note, so a return isn't lost once the request moves on.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_correction_requests', function (Blueprint $table) {
            $table->timestamp('applied_at')->nullable()->after('open_until');
            $table->timestamp('revised_at')->nullable()->after('applied_at');
            $table->json('history')->nullable()->after('revised_at');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_correction_requests', function (Blueprint $table) {
            $table->dropColumn(['applied_at', 'revised_at', 'history']);
        });
    }
};
