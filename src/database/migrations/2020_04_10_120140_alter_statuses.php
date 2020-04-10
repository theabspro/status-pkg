<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterStatuses extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('statuses', function (Blueprint $table) {
			$table->unsignedMediumInteger('display_order')->default(999)->after('company_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('statuses', function (Blueprint $table) {
			$table->dropColumn('display_order');
		});
	}
}
