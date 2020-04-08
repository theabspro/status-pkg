<?php
namespace Abs\StatusPkg\Database\Seeds;

use App\Permission;
use Illuminate\Database\Seeder;

class StatusPkgPermissionSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$permissions = [
			//Statuses
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'statuses',
				'display_name' => 'Statuses',
			],
			[
				'display_order' => 1,
				'parent' => 'statuses',
				'name' => 'add-status',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'statuses',
				'name' => 'edit-status',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'statuses',
				'name' => 'delete-status',
				'display_name' => 'Delete',
			],

		];
		Permission::createFromArrays($permissions);
	}
}