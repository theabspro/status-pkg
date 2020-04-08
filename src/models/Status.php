<?php

namespace Abs\StatusPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Status extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'statuses';
	public $timestamps = true;
	protected $fillable = [
		'code',
		'designation_id',
		'github_username',
		'personal_email',
		'alternate_mobile_number',
	];

	public function tasks() {
		return $this->hasManyThrough('Abs\ProjectPkg\Task', 'App\User', 'entity_id', 'assigned_to_id')->where('users.user_type_id', 1);
	}

	public function user() {
		return $this->hasOne('App\User', 'entity_id')->where('users.user_type_id', 1);
	}
	public function statusAttachment() {
		return $this->hasOne('Abs\BasicPkg\Attachment', 'entity_id')->where('attachment_of_id', 120)->where('attachment_type_id', 140);
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
