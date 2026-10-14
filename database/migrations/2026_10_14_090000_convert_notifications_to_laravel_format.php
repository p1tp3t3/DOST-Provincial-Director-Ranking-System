<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// The old custom notifications table (sender_id / receiver_id / content) was
// never written to. Replace it with Laravel's standard layout so notifications
// can use the 'database' and 'broadcast' channels. Fresh installs already get
// the new layout from the original create migration, so this is a no-op there.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('notifications', 'receiver_id')) return; // old layout only

        if (DB::table('notifications')->exists()) {
            throw new RuntimeException('The old notifications table has rows; migrate them before converting it.');
        }

        Schema::drop('notifications');
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Irreversible: the old layout was unused.
    }
};
