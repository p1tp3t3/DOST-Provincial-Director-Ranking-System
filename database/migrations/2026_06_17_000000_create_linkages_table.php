<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('linkages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provincial_director_id')->constrained('users');
            $table->year('year');
            $table->string('partner_organization');
            $table->string('title');
            $table->enum('type', ['MOA', 'MOU']);
            $table->date('date_signed');
            $table->text('signatories')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['provincial_director_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('linkages');
    }
};
