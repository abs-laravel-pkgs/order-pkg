<?php

namespace Abs\OrderPkg;
use Abs\OrderPkg\Order;
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
		$orders = Order::withTrashed()
			->select([
				'orders.id',
				'orders.question',
				DB::raw('orders.deleted_at as status'),
			])
			->where('orders.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->question)) {
					$query->where('orders.question', 'LIKE', '%' . $request->question . '%');
				}
			})
			->orderby('orders.id', 'desc');

		return Datatables::of($orders)
			->addColumn('question', function ($order) {
				$status = $order->status ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $order->question;
			})
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

	public function deleteOrder($id) {
		$delete_status = Order::withTrashed()->where('id', $id)->forceDelete();
		return response()->json(['success' => true]);
	}
}
