<?php

namespace Abs\StatusPkg;

use Abs\BasicPkg\Config;
use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Status extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'statuses';
	public $timestamps = true;
	protected $fillable = [
		'type_id',
		'name',
		'color',
		'display_order',
	];

	public function tasks() {
		return $this->hasManyThrough('Abs\ProjectPkg\Task', 'App\User', 'entity_id', 'assigned_to_id')->where('users.user_type_id', 1);
	}

	public function user() {
		return $this->hasOne('App\User', 'entity_id')->where('users.user_type_id', 1);
	}
	public function statusType() {
		return $this->hasOne('Abs\BasicPkg\Config')->where('configs.config_type_id', 20);
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
