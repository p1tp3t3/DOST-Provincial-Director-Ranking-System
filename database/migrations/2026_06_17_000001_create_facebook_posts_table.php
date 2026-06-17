<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provincial_director_id')->constrained('users');
            $table->year('year');
            $table->string('title');
            $table->string('post_url')->nullable();
            $table->enum('post_type', ['Text', 'Photo', 'Video', 'Link', 'Event'])->default('Text');
            $table->date('date_posted');
            $table->text('caption')->nullable();
            $table->timestamps();

            $table->index(['provincial_director_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_posts');
    }
};
