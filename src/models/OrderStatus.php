<?php

namespace Abs\OrderPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderStatus extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'order_statuses';
	protected $fillable = [
		'name',
	];

	public function smsTemplate() {
		return $this->belongsTo('Abs\SmsPkg\SmsTemplate');
	}

	public static function getList() {
		$statuses = self::where('company_id', config('custom.company_id'))
			->select('id', 'name', 'sms_template_id')
			->with([
				'smsTemplate',
				'smsTemplate.params',
			])
			->get();
		return $statuses;
	}

	public static function createFromObject($record_data) {

		$errors = [];
		$company = Company::where('code', $record_data->company)->first();
		if (!$company) {
			dump('Invalid Company : ' . $record_data->company);
			return;
		}

		$admin = $company->admin();
		if (!$admin) {
			dump('Default Admin user not found');
			return;
		}

		$type = Config::where('name', $record_data->type)->where('config_type_id', 89)->first();
		if (!$type) {
			$errors[] = 'Invalid Tax Type : ' . $record_data->type;
		}

		if (count($errors) > 0) {
			dump($errors);
			return;
		}

		$record = self::firstOrNew([
			'company_id' => $company->id,
			'name' => $record_data->tax_name,
		]);
		$record->type_id = $type->id;
		$record->created_by_id = $admin->id;
		$record->save();
		return $record;
	}

}
