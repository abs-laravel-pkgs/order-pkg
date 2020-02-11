<?php

namespace Abs\OrderPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'orders';
	public $timestamps = true;
	protected $fillable = [
		'billing_address_id',
		'shipping_address_id',
		'shipping_method_id',
		'payment_mode_id',
		'coupon_id',
	];

	public function billingAddress() {
		return $this->belongsTo('App\Address', 'billing_address_id');
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

	public static function createOrder($data, $cart) {
		$shipping_method = ShippingMethod::findOrFail($data['shipping_method_id']);

		$order = new Order;
		$order->fill($data);
		$order->company_id = config('custom.company_id');
		$order->sub_total = $cart['total'];
		$order->shipping_charge = $shipping_method->charge;

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
