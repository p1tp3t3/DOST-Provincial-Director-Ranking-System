<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Accomplishments per province, KPI and quarter, entered by the
        // provincial admin. The annual figure in provincial_director_kpis is
        // rolled up from these using each KPI's rollup_rule.
        Schema::create('kpi_quarter_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained('provinces');
            $table->foreignId('kpi_id')->constrained('kpis');
            $table->year('year');
            $table->unsignedTinyInteger('quarter'); // 1–4
            $table->decimal('accomplished', 18, 4)->nullable();
            // Director in office when the value was entered.
            $table->foreignId('provincial_director_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['province_id', 'kpi_id', 'year', 'quarter'], 'kqv_province_kpi_period_unique');
            $table->index(['province_id', 'year']);
        });

        // Last day the provinces can enter a quarter's accomplishments, set by
        // the super admin. After it, changes need an approved correction request.
        // The Q4 deadline also closes that year's targets.
        Schema::create('kpi_reporting_deadlines', function (Blueprint $table) {
            $table->id();
            $table->year('year');
            $table->unsignedTinyInteger('quarter');
            $table->date('deadline');
            $table->foreignId('set_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['year', 'quarter']);
        });

        // The provincial director's sign-off on a quarter's figures. Editing an
        // approved quarter clears its approval, so the director re-approves what
        // was actually changed.
        Schema::create('kpi_quarter_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained('provinces');
            $table->year('year');
            $table->unsignedTinyInteger('quarter');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['province_id', 'year', 'quarter']);
        });

        // Changing a locked quarter (or the year's targets, quarter = null): the
        // provincial admin asks, the regional director forwards (or returns it),
        // and the super admin approves or declines. Approval reopens the period
        // for that province until open_until.
        //   status: pending_regional → pending_central → approved | declined,
        //           or returned (by the regional director)
        Schema::create('kpi_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained('provinces');
            $table->year('year');
            $table->unsignedTinyInteger('quarter')->nullable();
            $table->text('reason');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('pending_regional');
            $table->foreignId('regional_reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('regional_reviewed_at')->nullable();
            $table->text('regional_note')->nullable();
            $table->foreignId('central_reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('central_reviewed_at')->nullable();
            $table->text('central_note')->nullable();
            $table->timestamp('open_until')->nullable();
            $table->timestamps();

            $table->index(['province_id', 'year']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_correction_requests');
        Schema::dropIfExists('kpi_quarter_approvals');
        Schema::dropIfExists('kpi_reporting_deadlines');
        Schema::dropIfExists('kpi_quarter_values');
    }
};
