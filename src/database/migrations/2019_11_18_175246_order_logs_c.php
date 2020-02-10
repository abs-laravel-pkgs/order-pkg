<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class OrderLogsC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('order_logs', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('order_id');
			$table->datetime('date');
			$table->boolean('notify_customer');
			$table->unsignedInteger('status_id');
			$table->text('comments')->nullable();
			$table->unsignedInteger('created_by_id')->nullable();

			$table->foreign('order_id')->references('id')->on('orders')->onDelete('CASCADE')->onUpdate('cascade');
			$table->foreign('status_id')->references('id')->on('order_statuses')->onDelete('CASCADE')->onUpdate('cascade');
			$table->foreign('created_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('order_logs');
	}
}
