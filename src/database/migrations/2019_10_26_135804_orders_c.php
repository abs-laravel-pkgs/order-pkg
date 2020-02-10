<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class OrdersC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('orders', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('company_id');
			$table->unsignedInteger('billing_address_id');
			$table->unsignedInteger('shipping_address_id');
			$table->unsignedInteger('shipping_method_id');
			$table->unsignedInteger('payment_mode_id');
			$table->unsignedInteger('coupon_id')->nullable();
			$table->unsignedDecimal('sub_total', 12, 2);
			$table->unsignedDecimal('shipping_charge', 5, 2);
			$table->unsignedDecimal('discount', 8, 2)->nullable();
			$table->unsignedDecimal('total', 12, 2);
			$table->unsignedInteger('status_id')->nullable();
			$table->unsignedInteger('created_by_id')->nullable();
			$table->unsignedInteger('updated_by_id')->nullable();
			$table->unsignedInteger('deleted_by_id')->nullable();
			$table->timestamps();
			$table->softDeletes();

			$table->foreign('company_id')->references('id')->on('companies')->onDelete('CASCADE')->onUpdate('cascade');
			$table->foreign('billing_address_id')->references('id')->on('addresses')->onDelete('CASCADE')->onUpdate('cascade');
			$table->foreign('shipping_address_id')->references('id')->on('addresses')->onDelete('CASCADE')->onUpdate('cascade');
			$table->foreign('shipping_method_id')->references('id')->on('shipping_methods')->onDelete('CASCADE')->onUpdate('cascade');
			$table->foreign('payment_mode_id')->references('id')->on('payment_modes')->onDelete('CASCADE')->onUpdate('cascade');
			$table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('status_id')->references('id')->on('order_statuses')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('created_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('orders');
	}
}
