<?php
namespace Abs\OrderPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use App\User;
use Illuminate\Database\Eloquent\Model;

class OrderLog extends Model {
	use SeederTrait;
	protected $table = 'order_logs';
	public $timestamps = false;
  protected $fillable = [
    'order_id',
    'date',
    'notify_customer',
    'status_id',
    'comments',
  ];
  protected $casts = [
    'notify_customer' => 'bool',
  ];

  public function status(): \Illuminate\Database\Eloquent\Relations\BelongsTo
  {
    return $this->belongsTo('App\OrderStatus', 'status_id');
  }

  public function order(): \Illuminate\Database\Eloquent\Relations\BelongsTo
  {
    return $this->belongsTo(\App\Order::class, 'order_id');
  }

  public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by_id');
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


  // Relationships to auto load
  public static function relationships($action = '', $format = ''): array
  {
    $relationships = [];

    if ($action === 'index') {
//      $relationships = array_merge($relationships, [
//        'billingAddress',
//        'paymentMode',
//        'status',
//      ]);
    } elseif ($action === 'read') {
      $relationships = array_merge($relationships, [
        'status',
        'createdBy',
      ]);
    } elseif ($action === 'options') {
      $relationships = array_merge($relationships, [
      ]);
    }

    return $relationships;
  }
}
