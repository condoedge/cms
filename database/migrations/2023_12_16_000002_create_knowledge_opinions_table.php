<?php

use Anonimatrix\PageEditor\Models\Wiki\KnowledgeOpinion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKnowledgeOpinionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('knowledge_opinions', function (Blueprint $table) {
            $table->id();
            $table->string('ip')->nullable();
            $table->enum('type', [KnowledgeOpinion::OPINION_LIKE, KnowledgeOpinion::OPINION_DISLIKE])->default('like'); 
            $table->timestamps();

            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('page_id')->constrained('pages');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('knowledge_opinions');
    }
}
