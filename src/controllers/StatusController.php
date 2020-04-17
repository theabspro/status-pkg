<?php

namespace Abs\StatusPkg;
use App\Config;
use Abs\StatusPkg\Status;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class StatusController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getStatusList(Request $request) {
		// dd($request->all());
		$statuses = Status::withTrashed()
			->join('configs as type', 'type.id', 'statuses.type_id')
			->select([
				'statuses.id',
				'type.name as type_name',
				'statuses.name',
				'statuses.color',
				'statuses.display_order',
				DB::raw('IF(statuses.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('statuses.company_id', Auth::user()->company_id)
		// ->where(function ($query) use ($request) {
		// 	if (!empty($request->color)) {
		// 		$query->where('statuses.color', 'LIKE', '%' . $request->color . '%');
		// 	}
		// })
			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('statuses.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->type_id)) {
					$query->where('statuses.type_id', $request->type_id);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->display_order)) {
					$query->where('statuses.display_order', $request->display_order);
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('statuses.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('statuses.deleted_at');
				}
			})
		;

		return Datatables::of($statuses)
			->addColumn('type_name', function ($statuses) {
				$status = $statuses->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $statuses->type_name;
			})
			->addColumn('action', function ($statuses) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-status')) {
					$output .= '<a href="#!/status-pkg/status/edit/' . $statuses->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-status')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#status-delete-modal" onclick="angular.element(this).scope().deleteStatus(' . $statuses->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getStatusFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$status = new Status;
			$action = 'Add';
		} else {
			$status = Status::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['status'] = $status;
		$this->data['type_list'] = $task_type_list = Collect(Config::select('id', 'name')->where('config_type_id', 20)->get())->prepend(['id' => '', 'name' => 'Select Type']);
		$this->data['action'] = $action;
		return response()->json($this->data);
	}
	public function getStatusFilterData() {
		$this->data['type_list'] = $task_type_list = Collect(Config::select('id', 'name')->where('config_type_id', 20)->get())->prepend(['id' => '', 'name' => 'Select Type']);

		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function saveStatus(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'type_id.required' => 'Type is Required',
				'type_id.unique' => 'Type is already taken',
				'name.required' => 'Name is Required',
				'name.unique' => 'Name is already taken',
				'name.min' => 'Name is Minimum 3 Charachers',
				'name.max' => 'Name is Maximum 191 Charachers',
				'color.required' => 'Color is Required',
				'color.min' => 'Color is Minimum 3 Charachers',
				'color.max' => 'Color is Maximum 255 Charachers',
			];
			$validator = Validator::make($request->all(), [
				'type_id' => [
					'required:true',
					'exists:configs,id',
					'unique:statuses,type_id,' . $request->id . ',id,company_id,' . Auth::user()->company_id . ',name,' . $request->name,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:statuses,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id . ',type_id,' . $request->type_id,
				],
				'color' => 'required|min:3|max:255',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$status = new Status;
				$status->company_id = Auth::user()->company_id;

				$status->created_by_id = Auth::user()->id;
			} else {
				$status = Status::withTrashed()->find($request->id);
				$status->updated_by_id = Auth::user()->id;
			}
			$status->fill($request->all());
			if ($request->status == 'Inactive') {
				$status->deleted_at = Carbon::now();
			} else {
				$status->deleted_at = NULL;
			}
			if ($request->display_order == NULL) {
				$status->display_order = 999;
			}
			$status->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Status Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Status Updated Successfully',
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

	public function deleteStatus(Request $request) {
		DB::beginTransaction();
		try {
			$status = Status::withTrashed()->where('id', $request->id)->first();
			if ($status) {
				$status = Status::withTrashed()->where('id', $request->id)->forceDelete();
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Status Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getTypeWiseStatus(Request $request) {

		$status_types = Config::where('configs.config_type_id', 20)
			->with([
				'statuses',				
			])
			->get();

		// $statuses = Status::withTrashed()
		// 	->join('configs as type', 'type.id', 'statuses.type_id')
		// 	->select([
		// 		'statuses.id',
		// 		'type.name as type_name',
		// 		'statuses.name',
		// 		'statuses.color',
		// 		'statuses.display_order',
		// 		DB::raw('IF(statuses.deleted_at IS NULL, "Active","Inactive") as status'),
		// 	])
		// 	->where('statuses.company_id', Auth::user()->company_id)

		// 	// ->orderBy('display_order','asc')
		// 	->get();
		// $type_list = Collect(Config::select('id', 'name')->where('config_type_id', 20)->get())->prepend(['id' => '', 'name' => 'Select Type']);

		return response()->json([
			'success' => true,
			'status_types' => $status_types,
			// 'type_list' => $type_list,
		]);		
	}
}
