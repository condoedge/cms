<?php

use Anonimatrix\PageEditor\Support\Facades\Features\Features;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->nullable()->constrained();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->integer('order')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->string('title', 500)->nullable();
            $table->string('permalink', 1000)->nullable();
            $table->string('exterior_background_color')->nullable();

            if(Features::hasFeature('teams')) {
                $table->foreignId('team_id')->nullable()->constrained();
            }

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pages');
    }
}
