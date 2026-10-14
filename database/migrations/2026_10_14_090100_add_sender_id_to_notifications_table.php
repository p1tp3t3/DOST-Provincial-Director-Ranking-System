<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Track who sent each notification; null = the system (e.g. scheduled
// reminders). Fresh installs already get the column from the create migration.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('notifications', 'sender_id')) return;

        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('sender_id')->nullable()->after('notifiable_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('notifications', 'sender_id')) return;

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sender_id');
        });
    }
};
