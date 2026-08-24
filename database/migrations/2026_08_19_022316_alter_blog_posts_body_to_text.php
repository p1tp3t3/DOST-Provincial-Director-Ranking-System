<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $posts = DB::table('blog_posts')->select('id', 'body')->get();

        DB::statement('ALTER TABLE blog_posts MODIFY body LONGTEXT NOT NULL');

        foreach ($posts as $post) {
            $paragraphs = json_decode($post->body, true) ?: [];
            $html = implode('', array_map(
                fn ($p) => '<p>' . e($p) . '</p>',
                array_filter($paragraphs, fn ($p) => trim((string) $p) !== '')
            ));

            DB::table('blog_posts')->where('id', $post->id)->update(['body' => $html]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $posts = DB::table('blog_posts')->select('id', 'body')->get();

        foreach ($posts as $post) {
            $plain = trim(strip_tags((string) $post->body));
            DB::table('blog_posts')->where('id', $post->id)->update([
                'body' => json_encode($plain !== '' ? [$plain] : ['']),
            ]);
        }

        DB::statement('ALTER TABLE blog_posts MODIFY body JSON NOT NULL');
    }
};
