<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnProductCodeToSoftwareTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('software', function (Blueprint $table) {
            $table->string('product_code')->after('id');
            $table->decimal('recommand_seling_price', 28,8)->default(0)->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('software', function (Blueprint $table) {
        //    $table->dropColumn('product_code');
        //    $table->dropColumn('recommand_seling_price');
        });
    }
}
