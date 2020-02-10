<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class OrderItemsC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('order_items', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('order_id');
			$table->unsignedInteger('item_id');
			$table->unsignedInteger('qty');
			$table->unsignedDecimal('rate');
			$table->unsignedDecimal('price');

			$table->foreign('order_id')->references('id')->on('orders')->onDelete('CASCADE')->onUpdate('cascade');
			$table->foreign('item_id')->references('id')->on('items')->onDelete('CASCADE')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('order_items');
	}
}
