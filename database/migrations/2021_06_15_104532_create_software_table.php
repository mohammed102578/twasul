<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSoftwareTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('software', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('featured')->default(0);
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('category_id');
            $table->unsignedInteger('sub_category_id')->nullable();
            $table->string('title');
            $table->string('image', 40);
            $table->string('document_file', 40);
            $table->string('upload_software', 40);
            $table->string('file_size')->nullable();
            $table->string('demo_url');
            $table->decimal('amount', 28,8)->default(0);
            $table->text('description');
            $table->tinyInteger('status')->default(0)->comment('Approved : 1, Cancel : 2, Pending : 0');
            $table->integer('favorite');
            $table->decimal('rating', 5,2)->default(0);
            $table->integer('likes')->default(0);
            $table->integer('dislike')->default(0);
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
        Schema::dropIfExists('software');
    }
}
