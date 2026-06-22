<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The original ENUM only listed five legacy values and was silently
        // truncating every other type the codebase writes (create, update,
        // delete, generate, export, view) — including the audit line written
        // when a super-admin deletes a user. Widen to a string so the column
        // doesn't need a migration each time the audit helper grows a verb.
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('type', 32)->change();
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->enum('type', [
                'log in', 'log out', 'province creation', 'registration', 'profile update',
            ])->change();
        });
    }
};
