<?php

namespace Abs\OrderPkg;

use Abs\CardPkg\Card;
use Abs\CompanyPkg\Traits\CompanyableTrait;
use Abs\EntityPkg\Entity;
use Abs\HelperPkg\Traits\SeederTrait;
use App\Address;
use App\Coupon;
use App\Item;
use App\Models\BaseModel;
use App\ShippingMethod;
use App\Company;
use App\Config;
use Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

class Order extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	use CompanyableTrait;
	protected $table = 'orders';
	public $timestamps = true;
	protected $fillable = [
		'ref_no',
		'sub_total',
		'total',
		'use_shipping_address',
		'ip',
		'key',
	];
	protected $casts = [
		'use_shipping_address' => 'bool',
		'sub_total' => 'float',
		'shipping_charge' => 'float',
		'total' => 'float',
	];
	public $fillableRelationships = [
		'company',
		'shipping_address',
		'billing_address',
		'shipping_method',
		'status',
		'order_items',
		'payment_mode',
	];

	public $relationshipRules = [
		'shipping_address' => [
			'required',
		],
		'shipping_method' => [
			'required',
		],
	];

	// Relationships to auto load
	public static function relationships($action = '', $format = ''): array
	{
		$relationships = [];

		if ($action === 'index') {
			$relationships = array_merge($relationships, [
				'billingAddress',
				'paymentMode',
				'status',
			]);
		} elseif ($action === 'read') {
			$relationships = array_merge($relationships, [
				'shippingAddress.country',
				'shippingAddress.state',
				'billingAddress.country',
				'billingAddress.state',
				'paymentMode',
				'shippingMethod',
				'status',
			]);
		} elseif ($action === 'save') {
			$relationships = array_merge($relationships, [
			]);
		} elseif ($action === 'options') {
			$relationships = array_merge($relationships, [
			]);
		}

		return $relationships;
	}

	public static function appendRelationshipCounts($action = '', $format = ''): array
	{
		$relationships = [];

		if ($action === 'index') {
			$relationships = array_merge($relationships, [
			]);
		} elseif ($action === 'options') {
			$relationships = array_merge($relationships, [
			]);
		}

		return $relationships;
	}

	public function card() {
		return $this->hasOne('Abs\CardPkg\Card', 'entity_id')->where('belongs_to_id', 1);
	}

	public function billingAddress() {
		return $this->belongsTo(Address::class, 'billing_address_id');
	}

	public function shippingAddress() {
		return $this->belongsTo('App\Address', 'shipping_address_id');
	}

	public function shippingMethod() {
		return $this->belongsTo('App\ShippingMethod');
	}

	public function paymentMode() {
		return $this->belongsTo('App\PaymentMode');
	}

	public function coupon() {
		return $this->belongsTo('App\Coupon');
	}

	public function status() {
		return $this->belongsTo('App\OrderStatus', 'status_id');
	}

	public function logs() {
		return $this->hasMany('App\OrderLog')->orderBy('date', 'desc');
	}

	public function createdBy() {
		return $this->belongsTo('App\User', 'created_by_id');
	}

	public function orderItems() {
		return $this->hasMany('App\OrderItem');
	}

	public function items() {
		return $this->hasMany('App\OrderItem');
	}

	public static function createOrder($data, $cart) {
		$shipping_method = ShippingMethod::findOrFail($data['shipping_method_id']);

		$order = new Order;
		$order->fill($data);
		$order->company_id = config('custom.company_id');
		$order->sub_total = $cart['total'];
		if($order->shippingMethod->free_min_amount){
			if($order->sub_total >= $order->shippingMethod->free_min_amount && $order->sub_total <= $order->shippingMethod->free_max_amount ){
				$order->shipping_charge = 0;
			}
			else{
				$order->shipping_charge = $shipping_method->charge;
			}
		}

		// dd($data['coupon_code']);
		if (isset($data['coupon_code'])) {
			$coupon = Coupon::where('company_id', config('custom.company_id'))->where('code', $data['coupon_code'])->first();
			if ($coupon) {
				$order->discount = $cart['total'] * $coupon->discount_percentage / 100;
				$order->coupon_id = $coupon->id;
			} else {
				$order->discount = 0;
			}
		} else {
			$order->discount = 0;
		}
		$order->total = $order->sub_total + $order->shipping_charge - $order->discount;
		if (Auth::check()) {
			$order->created_by_id = Auth::id();
		}
		$default_order_status = Entity::where([
			'company_id' => $order->company_id,
			'entity_type_id' => 2,
		])->first();
		if (!$default_order_status) {

		}

		$order->status_id = $default_order_status->name;
		$order->save();

		foreach ($cart['items'] as $item_id => $cart_item) {
			$item = Item::findOrFail($item_id);
			$order_item = new OrderItem();
			$order_item->order_id = $order->id;
			$order_item->item_id = $item->id;
			$order_item->qty = $cart_item['qty'];
			$order_item->rate = $item->special_price;
			$order_item->price = $order_item->qty * $order_item->rate;
			$order_item->save();
		}
		// dd($r->all());
		if ($data['payment_mode_id'] == 1) {
			$card = new Card;
			$card->fill($data['card']);
			$card->company_id = $order->company_id;
			$card->belongs_to_id = 1; //ORDER
			$card->entity_id = $order->id;
			$card->save();
		}
		// Cart::emptyCart();

		return $order;
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
