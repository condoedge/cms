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
        Schema::create('page_item_styles', function (Blueprint $table) {
            $table->id();
            $table->string('content', 1000)->nullable();
            $table->string('block_type')->nullable();
            $table->foreignId('page_item_id')->nullable()->constrained();
            $table->foreignId('page_id')->nullable()->constrained(); // If page_id is null, it's a generic style
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
        Schema::dropIfExists('page_item_styles');
    }
}
