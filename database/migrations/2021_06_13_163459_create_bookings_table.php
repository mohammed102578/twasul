<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('service_id')->nullable();
            $table->unsignedInteger('software_id')->nullable();
            $table->unsignedInteger('job_biding_id')->nullable();
            $table->unsignedInteger('qty')->default(0);
            $table->decimal('amount', 28,8)->default(0);
            $table->decimal('discount', 28,8)->default(0);
            $table->string('order_number',40);
            $table->string('extra_service')->nullable();
            $table->tinyInteger('status')->comment('unpaid : 0, Running : 1, Payable Seller : 2, Payable Buyer:3, Payable Both : 4, Paid : 5, Refund : 6');
            $table->tinyInteger('working_status')->comment('Pending : 0, Complet : 1, Delivered : 2 In-progress : 3, Cancel : 4, dispute : 5, expired : 6');
            $table->text('dispute_report')->nullable();
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
        Schema::dropIfExists('bookings');
    }
}
