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
        // A provincial admin gets one free edit per (director, year).
        // Once used, further edits require a regional admin to approve a request here.
        Schema::create('kpi_edit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provincial_director_id')->constrained('users');
            $table->foreignId('requested_by')->constrained('users');
            $table->year('year');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->dateTime('reviewed_at')->nullable();
            $table->text('response_note')->nullable();
            // Set once the single edit granted by an approval has been spent.
            $table->dateTime('used_at')->nullable();
            $table->timestamps();

            $table->index(['provincial_director_id', 'year']);
        });

        // Ledger of every consumed edit (the free one, plus one per approved request),
        // used to determine whether the free edit for a (director, year) is still
        // available.
        Schema::create('kpi_edit_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provincial_director_id')->constrained('users');
            $table->year('year');
            $table->foreignId('used_by')->constrained('users');
            $table->foreignId('kpi_edit_request_id')->nullable()->constrained('kpi_edit_requests');
            $table->timestamps();

            $table->index(['provincial_director_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_edit_usage');
        Schema::dropIfExists('kpi_edit_requests');
    }
};
