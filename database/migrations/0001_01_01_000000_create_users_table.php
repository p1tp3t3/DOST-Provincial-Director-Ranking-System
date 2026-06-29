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
        Schema::create('region', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('island_under', ['luzon', 'visayas', 'mindanao']);
            $table->timestamps();
        });
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')
                  ->constrained('region');
            $table->string('name');
            $table->enum('category', ['micro', 'small', 'medium', 'large']);
            $table->integer('num_plantilla_employees');
            $table->integer('num_municipalities');
            $table->integer('num_cities');
            $table->timestamps();
        });
        
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->enum('role', [
                'super_admin', 
                'sub_admin', 
                'regional_admin',
                'provincial_admin', 
                'provincial_sub_admin', 
                'provincial_director', 
                'employee'
            ]);
            $table->foreignId('region_id')
                  ->nullable()
                  ->constrained('region')
                  ->nullOnDelete();
            $table->string('dost_employee_id')->nullable()->unique();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('activate')
                  ->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('user_province', function (Blueprint $table) {
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->foreignId('province_id')
                  ->constrained('provinces')
                  ->cascadeOnDelete();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('prefix')->nullable();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('profile_picture')->nullable();
            $table->string('length_of_service');
            $table->json('education_attainment')->nullable();
            $table->string('suffix')->nullable();
        });


        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->foreignId('profile_id')->constrained('profiles');
            $table->string('position');
            $table->enum('status', ['permanent', 'cos', 'jo']);
            $table->json('work_specification');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('region');
        Schema::dropIfExists('provinces');
        Schema::dropIfExists('users');
        Schema::dropIfExists('user_province');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('profiles');
        Schema::dropIfExists('employee_profiles');
        Schema::dropIfExists('sessions');
    }
};
