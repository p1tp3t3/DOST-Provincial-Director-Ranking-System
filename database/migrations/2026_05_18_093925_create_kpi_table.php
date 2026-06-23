<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 3 official PSTD matrix categories: CORE (0.6), STRATEGIC (0.3), SUPPORT (0.1)
        Schema::create('kpi_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 50);
            $table->decimal('weight', 5, 4);
            $table->unsignedTinyInteger('sort_order');
        });

        // 37 scored KPIs + supporting input-only rows (e.g. ongoing/delinquent SETUP counts
        // used to derive the % Delinquent SETUP KPI). is_scored=false means the row is
        // collected from PSTDs but does not directly contribute to the weighted score.
        Schema::create('kpis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('kpi_categories');
            $table->string('code', 60)->unique();
            $table->text('name');
            $table->decimal('weight', 6, 4)->default(0);
            $table->boolean('is_scored')->default(true);
            $table->boolean('inverse_scoring')->default(false);
            $table->string('derivation_type', 50)->nullable();
            $table->unsignedSmallInteger('sort_order');
        });

        Schema::create('provincial_director_kpis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provincial_director_id')->constrained('users');
            $table->foreignId('kpi_id')->constrained('kpis');
            $table->text('target')->nullable();
            $table->text('accomplished')->nullable();
            $table->year('year');
            $table->index(['provincial_director_id', 'year']);
            // Lets updateOrCreate() find the existing row by (director, kpi, year)
            $table->unique(['provincial_director_id', 'kpi_id', 'year'], 'pdk_director_kpi_year_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provincial_director_kpis');
        Schema::dropIfExists('kpis');
        Schema::dropIfExists('kpi_categories');
    }
};
