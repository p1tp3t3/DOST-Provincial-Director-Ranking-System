<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // KPI values belong to the provincial office, not to whoever is director:
    // replacing (or deleting) a director must not take the province's history
    // with them. provincial_director_id stays, as "director in office when the
    // value was entered". Also records who last changed each value, and when.
    public function up(): void
    {
        Schema::table('provincial_director_kpis', function (Blueprint $table) {
            $table->foreignId('province_id')->nullable()->after('id')->constrained('provinces');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Each director belongs to exactly one province.
        DB::statement('
            UPDATE provincial_director_kpis
            SET province_id = (
                SELECT MIN(up.province_id) FROM user_province up
                WHERE up.user_id = provincial_director_kpis.provincial_director_id
            )
        ');

        Schema::table('provincial_director_kpis', function (Blueprint $table) {
            $table->dropForeign(['provincial_director_id']);
            $table->dropUnique('pdk_director_kpi_year_unique');
        });

        Schema::table('provincial_director_kpis', function (Blueprint $table) {
            $table->foreignId('provincial_director_id')->nullable()->change();
            $table->foreign('provincial_director_id')->references('id')->on('users')->nullOnDelete();
            $table->unique(['province_id', 'kpi_id', 'year'], 'pdk_province_kpi_year_unique');
            $table->index(['province_id', 'year']);
        });

        // Evidence records (linkages, Facebook posts) belong to the province too,
        // so their counts survive a change of director — and deleting the
        // director's account no longer has to delete them.
        foreach (['linkages', 'facebook_posts'] as $records) {
            Schema::table($records, function (Blueprint $table) {
                $table->foreignId('province_id')->nullable()->after('id')->constrained('provinces');
                $table->index(['province_id', 'year']);
                $table->dropForeign(['provincial_director_id']);
            });

            Schema::table($records, function (Blueprint $table) {
                $table->foreignId('provincial_director_id')->nullable()->change();
                $table->foreign('provincial_director_id')->references('id')->on('users')->nullOnDelete();
            });

            DB::statement("
                UPDATE {$records}
                SET province_id = (
                    SELECT MIN(up.province_id) FROM user_province up
                    WHERE up.user_id = {$records}.provincial_director_id
                )
            ");
        }
    }

    public function down(): void
    {
        foreach (['linkages', 'facebook_posts'] as $records) {
            Schema::table($records, function (Blueprint $table) {
                $table->dropForeign(['province_id']);
                $table->dropIndex(['province_id', 'year']);
                $table->dropColumn('province_id');
                $table->dropForeign(['provincial_director_id']);
            });

            Schema::table($records, function (Blueprint $table) {
                $table->foreignId('provincial_director_id')->nullable(false)->change();
                $table->foreign('provincial_director_id')->references('id')->on('users');
            });
        }

        Schema::table('provincial_director_kpis', function (Blueprint $table) {
            $table->dropForeign(['provincial_director_id']);
            $table->dropUnique('pdk_province_kpi_year_unique');
            $table->dropIndex(['province_id', 'year']);
        });

        Schema::table('provincial_director_kpis', function (Blueprint $table) {
            $table->foreignId('provincial_director_id')->nullable(false)->change();
            $table->foreign('provincial_director_id')->references('id')->on('users');
            $table->unique(['provincial_director_id', 'kpi_id', 'year'], 'pdk_director_kpi_year_unique');
            $table->dropForeign(['province_id']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['province_id', 'updated_by', 'created_at', 'updated_at']);
        });
    }
};
