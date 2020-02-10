<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class OrderStatusesU1 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('order_statuses', function (Blueprint $table) {
			$table->unsignedInteger('sms_template_id')->nullable()->after('name');
			$table->foreign('sms_template_id')->references('id')->on('sms_templates')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('order_statuses', function (Blueprint $table) {
			$table->dropForeign('order_statuses_sms_template_id_foreign');
			$table->dropColumn('sms_template_id');
		});
	}
}
