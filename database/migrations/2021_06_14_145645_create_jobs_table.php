<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('title')->nullable();
            $table->string('image', 40)->nullable();
            $table->unsignedInteger('category_id')->nullable();
            $table->unsignedInteger('sub_category_id')->nullable();
            $table->decimal('amount', 28,8)->default(0);
            $table->integer('delivery_time')->nullable();
            $table->string('skill')->nullable();
            $table->text('description');
            $table->text('requirements');
            $table->tinyInteger('status')->default(0)->comment('Pending : 0, Approved : 1, Cancel : 2');
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
        Schema::dropIfExists('jobs');
    }
}
