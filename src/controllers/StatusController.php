<?php

namespace Abs\StatusPkg;
use Abs\BasicPkg\Attachment;
use Abs\StatusPkg\Status;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Validator;
use Yajra\Datatables\Datatables;

class StatusController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getStatusList(Request $request) {
		$statuses = Status::withTrashed()
			->join('configs as type', 'type.id', 'statuses.type_id')
			->select([
				'statuses.id',
				'type.name as type_name',
				'statuses.name',
				'statuses.color',
				DB::raw('IF(statuses.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('statuses.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->code)) {
					$query->where('statuses.code', 'LIKE', '%' . $request->code . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->first_name)) {
					$query->where('u.first_name', 'LIKE', '%' . $request->first_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->last_name)) {
					$query->where('u.last_name', 'LIKE', '%' . $request->last_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->mobile_number)) {
					$query->where('u.mobile_number', 'LIKE', '%' . $request->mobile_number . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->user_name)) {
					$query->where('u.username', 'LIKE', '%' . $request->user_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->designation_id)) {
					$query->where('statuses.designation_id', $request->designation_id);
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
			->addColumn('code', function ($status) {
				$status = $status->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $status->code;
			})
			->addColumn('action', function ($status) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-status')) {
					$output .= '<a href="#!/status-pkg/status/edit/' . $status->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-status')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#status-delete-modal" onclick="angular.element(this).scope().deleteStatus(' . $status->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
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
			$status->password_change = 'Yes';
		} else {
			$status = Status::withTrashed()->with('user', 'statusAttachment')->find($id);
			$action = 'Edit';
			$status->password_change = 'No';
			$this->data['status_attachment'] = Attachment::select('name')
				->where('attachment_of_id', 120) //ATTACHMENT OF STATUS
				->where('attachment_type_id', 140) //ATTACHMENT TYPE OF STATUS
				->where('entity_id', $status->id)
				->first();
		}
		$this->data['success'] = true;
		$this->data['status'] = $status;
		$this->data['designation_list'] = collect(Designation::select('name', 'id')->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '', 'name' => 'Select Designation']);
		$this->data['action'] = $action;
		return response()->json($this->data);
	}
	public function getStatusFilterData() {
		$this->data['designation_list'] = collect(Designation::select('name', 'id')->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '', 'name' => 'Select Designation']);
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function saveStatus(Request $request) {
		//dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Code is Required',
				'code.unique' => 'Code is already taken',
				'code.min' => 'Code is Minimum 3 Charachers',
				'code.max' => 'Code is Maximum 64 Charachers',
				'first_name.max' => 'Description is Maximum 255 Charachers',
				'personal_email.unique' => 'Personal Email is already taken',
				'alternate_mobile_number.max' => 'Alternate Mobile Number is Maximum 10 Charachers',
				'github_username.max' => 'Github Username is Maximum 64 Charachers',
			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'required:true',
					'min:3',
					'max:64',
					'unique:statuses,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'personal_email' => [
					'nullable',
					'min:3',
					'max:64',
					'unique:statuses,personal_email,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'alternate_mobile_number' => 'nullable|max:10',
				'github_username' => 'nullable|max:64',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}
			$user_error_messages = [
				'first_name.required' => 'First Name is Required',
				'first_name.min' => 'First Name is Minimum 3 Charachers',
				'first_name.max' => 'First Name is Maximum 32 Charachers',
				'last_name.required' => 'Last Name is Required',
				'last_name.min' => 'Last Name is Minimum 3 Charachers',
				'last_name.max' => 'Last Name is Maximum 32 Charachers',
				'email.required' => 'Email is Required',
				'email.min' => 'Email is Minimum 3 Charachers',
				'email.max' => 'Email is Maximum 191 Charachers',
				'email.unique' => 'Official Email is already taken',
				'mobile_number.required' => 'Mobile Number is Required',
				'mobile_number.min' => 'Mobile Number is Minimum 10 Charachers',
				'mobile_number.max' => 'Mobile Number is Maximum 10 Charachers',
				'mobile_number.unique' => 'Mobile Number is already taken',
				'username.required' => 'Username Number is Required',
				'username.min' => 'Username is Minimum 3 Charachers',
				'username.max' => 'Username is Maximum 32 Charachers',
				'username.unique' => 'Username is already taken',
			];
			$user_validator = Validator::make($request->user, [
				'first_name' => [
					'required:true',
					'min:3',
					'max:32',
				],
				'last_name' => [
					'required:true',
					'min:3',
					'max:32',
				],
				'email' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:users,email,' . $request->id . ',entity_id',
				],
				'mobile_number' => [
					'required:true',
					'min:10',
					'max:10',
					'unique:users,mobile_number,' . $request->id . ',entity_id',
				],
				'username' => [
					'required:true',
					'min:3',
					'max:32',
					'unique:users,username,' . $request->id . ',entity_id',
				],
			], $user_error_messages);
			if ($user_validator->fails()) {
				return response()->json(['success' => false, 'errors' => $user_validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$status = new Status;
				$status->company_id = Auth::user()->company_id;
				$user = new User;
				$user->company_id = Auth::user()->company_id;
				$user->created_by_id = Auth::user()->id;
			} else {
				$status = Status::withTrashed()->find($request->id);
				$user = User::withTrashed()->where([
					'entity_id' => $request->id,
					'user_type_id' => 1,
				])
					->first();
				$user->updated_by_id = Auth::user()->id;
			}
			$status->fill($request->all());
			if ($request->status == 'Inactive') {
				$status->deleted_at = Carbon::now();
				$user->deleted_by_id = Auth::user()->id;
			} else {
				$user->deleted_by_id = NULL;
				$status->deleted_at = NULL;
			}
			$status->save();
			//dd($status);

			$user->fill($request->user);
			$user->has_mobile_login = 0;
			$user->entity_id = $status->id;
			$user->user_type_id = 1;
			$user->save();

			//Status Profile Attachment
			$status_images_des = storage_path('app/public/status/attachments/');
			//dump($status_images_des);
			Storage::makeDirectory($status_images_des, 0777);
			if (!empty($request['attachment'])) {
				if (!File::exists($status_images_des)) {
					File::makeDirectory($status_images_des, 0777, true);
				}
				$remove_previous_attachment = Attachment::where([
					'entity_id' => $status->id,
					'attachment_of_id' => 120,
					'attachment_type_id' => 140,
				])->first();
				if (!empty($remove_previous_attachment)) {
					$img_path = $status_images_des . $remove_previous_attachment->name;
					if (File::exists($img_path)) {
						File::delete($img_path);
					}
					$remove = $remove_previous_attachment->forceDelete();
				}

				/*$exists_path=storage_path('app/public/status/attachments/'.$status->id.'/');
					//dd($exists_path);
					if (is_dir($exists_path))
					{
						unlink($exists_path);
				*/
				$extension = $request['attachment']->getClientOriginalExtension();
				$request['attachment']->move(storage_path('app/public/status/attachments/'), $status->id . '.' . $extension);
				$status_attachement = new Attachment;
				$status_attachement->company_id = Auth::user()->company_id;
				$status_attachement->attachment_of_id = 120; //ATTACHMENT OF STATUS
				$status_attachement->attachment_type_id = 140; //ATTACHMENT TYPE  STATUS
				$status_attachement->entity_id = $status->id;
				$status_attachement->name = $status->id . '.' . $extension;
				$status_attachement->save();
				$user->profile_image_id = $status_attachement->id;
				$user->save();

			}

			// $activity = new ActivityLog;
			// $activity->date_time = Carbon::now();
			// $activity->user_id = Auth::user()->id;
			// $activity->module = 'Statuses';
			// $activity->entity_id = $status->id;
			// $activity->entity_type_id = 1420;
			// $activity->activity_id = $request->id == NULL ? 280 : 281;
			// $activity->activity = $request->id == NULL ? 280 : 281;
			// $activity->details = json_encode($activity);
			// $activity->save();

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
				$user = User::withTrashed()->where([
					'entity_id' => $request->id,
					'user_type_id' => 1,
				])
					->forceDelete();
				$status = Status::withTrashed()->where('id', $request->id)->forceDelete();
				/*$activity = new ActivityLog;
					$activity->date_time = Carbon::now();
					$activity->user_id = Auth::user()->id;
					$activity->module = 'Statuses';
					$activity->entity_id = $request->id;
					$activity->entity_type_id = 1420;
					$activity->activity_id = 282;
					$activity->activity = 282;
					$activity->details = json_encode($activity);
				*/

				DB::commit();
				return response()->json(['success' => true, 'message' => 'Status Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
