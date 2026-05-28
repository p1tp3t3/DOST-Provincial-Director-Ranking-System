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
        Schema::create('provincial_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provincial_admin_id')->constrained('users');
            $table->json('provincial_director_details');
            $table->string('employee_list_csv_file');
            $table->text('description')->nullable();
            $table->enum('status', ['rejected', 'pending', 'approved'])->default('pending');
            $table->text('status_description')->nullable();
            $table->dateTime('status_since')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provincial_members');
    }
};
