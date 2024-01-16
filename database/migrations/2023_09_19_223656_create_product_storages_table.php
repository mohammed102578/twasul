<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductStoragesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_storages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->charset('utf8mb4')->nullable();
            $table->string('sku')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->string('price')->nullable();
            $table->string('regular_price')->nullable();
            $table->longText('description')->charset('utf8mb4')->nullable();
            $table->longText('short_description')->charset('utf8mb4')->nullable();
            $table->string('sale_price')->nullable();
            $table->string('slug')->charset('utf8mb4')->nullable();
            $table->string('permalink')->nullable();
            $table->date('date_created')->nullable();
            $table->date('date_created_gmt')->nullable();
            $table->date('date_modified')->nullable();
            $table->date('date_modified_gmt')->nullable();
            $table->string('featured')->nullable();
            $table->string('catalog_visibility')->nullable();
            $table->date('date_on_sale_from')->nullable();
            $table->date('date_on_sale_from_gmt')->nullable();
            $table->date('date_on_sale_to')->nullable();
            $table->date('date_on_sale_to_gmt')->nullable();
            $table->string('on_sale')->nullable();
            $table->string('purchasable')->nullable();
            $table->string('total_sales')->nullable();
            $table->string('virtual')->nullable();
            $table->string('downloadable')->nullable();
            $table->string('download_limit')->nullable();
            $table->string('download_expiry')->nullable();
            $table->string('external_url')->nullable();
            $table->string('button_text')->nullable();
            $table->string('tax_status')->nullable();
            $table->string('tax_class')->nullable();
            $table->string('manage_stock')->nullable();
            $table->string('stock_quantity')->nullable();
            $table->string('backorders')->nullable();
            $table->string('backorders_allowed')->nullable();
            $table->string('backordered')->nullable();
            $table->string('low_stock_amount')->nullable();
            $table->string('sold_individually')->nullable();
            $table->string('weight')->nullable();
            $table->string('dimensions')->nullable();
            $table->integer('shipping_required')->nullable();
            $table->integer('shipping_taxable')->nullable();
            $table->string('shipping_class')->nullable();
            $table->string('shipping_class_id')->nullable();
            $table->string('reviews_allowed')->nullable();
            $table->string('average_rating')->nullable();
            $table->string('rating_count')->nullable();
            $table->string('upsell_ids')->nullable();
            $table->string('cross_sell_ids')->nullable();
            $table->string('parent_id')->nullable();
            $table->string('purchase_note')->nullable();
            $table->longText('tags')->nullable();
            $table->string('menu_order')->nullable();
            $table->longText('price_html')->nullable();
            $table->string('stock_status')->nullable();
            $table->string('has_options')->nullable();
            $table->string('post_password')->nullable();
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
        Schema::dropIfExists('product_storages');
    }
}
