<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Accomplishments are entered per quarter and rolled up into the annual
    // figure that gets ranked. How a KPI's quarters combine depends on what it
    // measures, so each KPI carries its own rule (editable on the KPI Catalog):
    //   sum     — counts and amounts: add the quarters up (the default)
    //   latest  — running totals such as "ongoing projects": the last quarter entered
    //   average — rates and ratings: the mean of the quarters entered
    public function up(): void
    {
        Schema::table('kpis', function (Blueprint $table) {
            $table->enum('rollup_rule', ['sum', 'latest', 'average'])->default('sum')->after('derivation_type');
        });

        DB::table('kpis')
            ->whereIn('code', ['input_setup_ongoing_count', 'input_setup_delinquent_count'])
            ->update(['rollup_rule' => 'latest']);

        DB::table('kpis')
            ->whereIn('code', ['core_setup_refund_rate', 'supp_pct_programs_risk_analysis', 'supp_customer_satisfaction'])
            ->update(['rollup_rule' => 'average']);
    }

    public function down(): void
    {
        Schema::table('kpis', function (Blueprint $table) {
            $table->dropColumn('rollup_rule');
        });
    }
};
