<?php
namespace Abs\OrderPkg\Database\Seeds;

use App\Permission;
use Illuminate\Database\Seeder;

class OrderPkgPermissionSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$permissions = [
			//FAQ
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'orders',
				'display_name' => 'Orders',
			],
			[
				'display_order' => 1,
				'parent' => 'orders',
				'name' => 'add-order',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'orders',
				'name' => 'delete-order',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'orders',
				'name' => 'delete-order',
				'display_name' => 'Delete',
			],

		];
		Permission::createFromArrays($permissions);
	}
}