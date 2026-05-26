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
            $table->json('sub_rows');
            $table->timestamps();
        });
        
        Schema::create('provincial_director_kpis', function (Blueprint $table) {
            $table->foreignId('provincial_director_id')->constrained('users');
            $table->foreignId('kpi_id')->constrained('kpis');
            $table->json('outcome');
            $table->year('year');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpis');
        Schema::dropIfExists('provincial_director_kpis');
    }
};
