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
        Schema::create('kpis', function (Blueprint $table) {
            $table->id();
            $table->string('outcome_title');
            $table->timestamps();
        });

        Schema::create('kpi_subrows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_id')->constrained('kpis');
            $table->text('description');
        });
        
        Schema::create('provincial_director_kpis', function (Blueprint $table) {
            $table->foreignId('provincial_director_id')->constrained('users');
            $table->foreignId('kpi_id')->constrained('kpis');
            $table->foreignId('kpi_subrow_id')->constrained('kpi_subrows');
            $table->decimal('target')->nullable();
            $table->decimal('accomplished')->nullable();
            $table->year('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpis');
        Schema::dropIfExists('kpi_subrows');
        Schema::dropIfExists('provincial_director_kpis');
    }
};
