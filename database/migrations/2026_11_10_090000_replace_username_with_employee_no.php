<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// The employee no. (users.dost_employee_id) replaces the username everywhere:
// it's what people sign in with (along with their email) and what's shown to
// identify an account. Accounts that never had one (the super admin, sub admin
// and provincial admins) get their old username as their employee no., so
// they can still sign in with the same text; an admin can change it afterwards.
return new class extends Migration
{
    public function up(): void
    {
        $missing = DB::table('users')
            ->where(fn ($q) => $q->whereNull('dost_employee_id')->orWhere('dost_employee_id', ''))
            ->orderBy('id')
            ->get(['id', 'username']);

        foreach ($missing as $user) {
            $employeeNo = $user->username ?: "user-{$user->id}";
            if (DB::table('users')->where('dost_employee_id', $employeeNo)->exists()) {
                $employeeNo .= "-{$user->id}";   // already someone else's employee no.
            }
            DB::table('users')->where('id', $user->id)->update(['dost_employee_id' => $employeeNo]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('dost_employee_id')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->after('dost_employee_id');
        });

        // The employee no. is the closest thing to the old username.
        DB::table('users')->update(['username' => DB::raw('dost_employee_id')]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->change();
            $table->unique('username');
            $table->string('dost_employee_id')->nullable()->change();
        });
    }
};
