<?php

namespace Abs\OrderPkg;
use Abs\OrderPkg\Order;
use Abs\OrderPkg\OrderLog;
use Abs\OrderPkg\OrderStatus;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class OrderController extends Controller {

	private $company_id;
	public function __construct() {
		$this->data['theme'] = config('custom.admin_theme');
		$this->company_id = config('custom.company_id');
	}

	public function getOrders(Request $request) {
		$this->data['orders'] = Order::
			select([
			'orders.question',
			'orders.answer',
		])
			->where('orders.company_id', $this->company_id)
			->orderby('orders.display_order', 'asc')
			->get()
		;
		$this->data['success'] = true;

		return response()->json($this->data);

	}

	public function getOrderList(Request $request) {
		$orders = Order::
			join('order_statuses as status', 'status.id', 'orders.status_id')
			->join('addresses as ba', 'orders.billing_address_id', 'ba.id')
			->join('addresses as sa', 'orders.shipping_address_id', 'sa.id')
		// ->where('orders.created_by_id', Auth::id())
			->where('orders.company_id', config('custom.company_id'))
			->select([
				'orders.id as id',
				DB::raw('DATE_FORMAT(orders.created_at,"%d %b %Y %h:%i %p") as date'),
				DB::raw('CONCAT(ba.first_name," ",ba.last_name) as billing_name'),
				DB::raw('CONCAT(sa.first_name," ",sa.last_name) as shipping_name'),
				'orders.total as total',
				'status.name as status',
			])
			->orderBy('orders.id', 'DESC')
		;

		return Datatables::of($orders)
			->addColumn('action', function ($order) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img2 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/eye.svg');
				$img2_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/eye-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				$output .= '<a href="#!/order-pkg/order/edit/' . $order->id . '" id = "" ><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '"></a>
					<a href="#!/order-pkg/order/view/' . $order->id . '" id = "" ><img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '"></a>
					<a href="javascript:;"  data-toggle="modal" data-target="#order-delete-modal" onclick="angular.element(this).scope().deleteOrderconfirm(' . $order->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete_active . '" onmouseout=this.src="' . $img_delete . '"></a>
					';
				return $output;
			})
			->make(true);
	}

	public function getOrderFormData(Request $r) {
		$id = $r->id;
		if (!$id) {
			$order = new Order;
			$action = 'Add';
		} else {
			$order = Order::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['order'] = $order;
		$this->data['action'] = $action;

		return response()->json($this->data);
	}

	public function saveOrder(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Order Code is Required',
				'code.max' => 'Maximum 255 Characters',
				'code.min' => 'Minimum 3 Characters',
				'code.unique' => 'Order Code is already taken',
				'name.required' => 'Order Name is Required',
				'name.max' => 'Maximum 255 Characters',
				'name.min' => 'Minimum 3 Characters',
			];
			$validator = Validator::make($request->all(), [
				'question' => [
					'required:true',
					'max:255',
					'min:3',
					'unique:orders,question,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'answer' => 'required|max:255|min:3',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$order = new Order;
				$order->created_by_id = Auth::user()->id;
				$order->created_at = Carbon::now();
				$order->updated_at = NULL;
			} else {
				$order = Order::withTrashed()->find($request->id);
				$order->updated_by_id = Auth::user()->id;
				$order->updated_at = Carbon::now();
			}
			$order->fill($request->all());
			$order->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$order->deleted_at = Carbon::now();
				$order->deleted_by_id = Auth::user()->id;
			} else {
				$order->deleted_by_id = NULL;
				$order->deleted_at = NULL;
			}
			$order->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'FAQ Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'FAQ Updated Successfully',
				]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'error' => $e->getMessage(),
			]);
		}
	}
	public function viewOrderAdmin(Request $r) {
		$order_id = $r->id;
		$order = Order::with([
			'billingAddress',
			'shippingAddress',
			'shippingMethod',
			'paymentMode',
			'coupon',
			'status',
			'createdBy',
			'logs',
			'logs.status',
			'orderItems',
			'orderItems.item',
			'orderItems.item.category',
			'orderItems.item.category.packageType',
			'orderItems.item.strength',
			'orderItems.item.strength.type',
		])->find($order_id);
		if (!$order) {
			return response()->json(['success' => false, 'error' => 'Order not found']);
		}

		$order->date = date('d/m/Y', strtotime($order->created_at));
		$order->html_shipping_address = $order->shippingAddress->formatted_address;
		$order->html_billing_address = $order->billingAddress->formatted_address;

		if ($order->paymentMode->id == 1) {
			//Card
			$order->card->number = 'XXXX XXXX XXXX ' . substr($order->card->number, 14);
			$order->card->type;
		}

		$status_list = OrderStatus::getList();
		foreach ($status_list as $status) {
			if ($status->smsTemplate) {
				// dd($status);
				foreach ($status->smsTemplate->params as $key => &$param) {
					if ($param->type_id == 140) {
						//Customer Name
						$param->default_value = $order->billingAddress->first_name . ' ' . $order->billingAddress->last_name;
					} elseif ($param->type_id == 141) {
						//Order Amount
						$param->default_value = $order->total;
					} elseif ($param->type_id == 143) {
						//Current Date
						$param->default_value = date('d M Y');
					}
				}
			}
		}

		$extras = [
			'status_list' => $status_list,
		];
		return response()->json(['success' => true, 'order' => $order, 'extras' => $extras]);
	}

	public function addOrderLog(Request $r) {
		DB::beginTransaction();
		$validator = Validator::make($r->all(), [
			'log.order_id' => ['required'], //, 'confirmed'
			'log.status_id' => ['required'], //, 'confirmed'
			'log.notify_customer' => ['required'], //, 'confirmed'
		]);
		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => $validator->errors(),
			]);
		}

		$order = Order::find($r->log['order_id']);
		if (!$order) {
			return response()->json([
				'success' => false,
				'error' => 'Order Not Found',
			]);
		}

		$log = new OrderLog($r->log);
		$log->date = date('Y-m-d H:i:s');
		$log->notify_customer = $r->log['notify_customer'] == 'true' ? 1 : 0;
		$log->created_by_id = Auth::id();
		$log->save();

		$order->status_id = $log->status_id;
		$order->save();

		// return (new OrderStatusChanged($order, $log))->render();
		if ($log->notify_customer) {
			$to_email = config('custom.DEBUG_EMAIL') ? config('custom.DEBUG_EMAIL_ADDRESS') : $order->billingAddress->email;
			Mail::to($to_email)
				->bcc('abdulpro@gmail.com')
				->send(new OrderStatusChanged($order, $log));
		}

		if ($r->log['send_sms'] && $order->status->smsTemplate) {
			if ($order->billingAddress->country->mobile_code) {

				$mobile_code = config('custom.DEBUG_SMS') ? '91' : $order->billingAddress->country->mobile_code;
				$mobile_number = config('custom.DEBUG_SMS') ? config('custom.DEBUG_MOBILE') : $order->billingAddress->mobile_number;

				$message = $order->status->smsTemplate->content;
				$message = vsprintf($message, $r->status[$order->status->smsTemplate->id]);

				try {
					Twilio::message('+' . $mobile_code . $mobile_number, $message);
				} catch (\Exception $e) {
					dd($e);
				}
			}
		}

		DB::commit();
		return response()->json([
			'success' => true,
			'message' => 'Order Status Changed',
		]);
	}

	public function deleteOrder($id) {
		$delete_status = Order::withTrashed()->where('id', $id)->forceDelete();
		return response()->json(['success' => true]);
	}
}
