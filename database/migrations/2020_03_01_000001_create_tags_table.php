<?php

use Anonimatrix\PageEditor\Models\Tags\Tag;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable()->constrained();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('name');
            $table->string('description', 500)->nullable();
            $table->string('type');
            $table->string('context')->default(Tag::TAG_CONTEXT_ALL);
            $table->foreignId('tag_id')->nullable()->constrained('tags');
            $table->timestamps();
        });

        Schema::create('taggable_tag', function (Blueprint $table) {
            $table->id();
            $table->morphs('taggable');
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('taggable_tag');
        Schema::dropIfExists('tags');
    }
}
