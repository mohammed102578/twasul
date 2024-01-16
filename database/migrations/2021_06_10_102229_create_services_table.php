<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('featured')->default(0);
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('category_id')->nullable();
            $table->unsignedInteger('sub_category_id')->nullable();
            $table->string('title')->nullable();
            $table->string('image')->nullable();
            $table->decimal('price', 28,8)->default(0);
            $table->unsignedInteger('delivery_time')->nullable();
            $table->string('tag')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('favorite')->nullable();
            $table->decimal('rating', 5,2)->nullable();
            $table->unsignedInteger('likes')->nullable();
            $table->unsignedInteger('dislike')->nullable();
            $table->tinyInteger('status')->default(0)->comment("Approved : 1", "Cancel : 2");
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
        Schema::dropIfExists('services');
    }
}
