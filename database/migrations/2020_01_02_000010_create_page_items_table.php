<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePageItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('page_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('page_id')->constrained();
            $table->foreignId('page_item_id')->nullable()->constrained();

            $table->foreignId('group_page_item_id')->nullable()->constrained('page_items');

            $table->string('name_pi')->nullable();

            $table->integer('order')->nullable();
            $table->string('block_type')->nullable();
            $table->string('title', 500)->nullable();
            $table->text('content')->nullable();
            $table->string('image', 1000)->nullable();
            $table->string('classes', 1000)->nullable();

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
        Schema::dropIfExists('page_items');
    }
}
