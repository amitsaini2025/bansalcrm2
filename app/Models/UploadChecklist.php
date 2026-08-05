<?php
namespace App\Models;

use Kyslik\ColumnSortable\Sortable;
use Illuminate\Database\Eloquent\Model;

class UploadChecklist extends Model
{
	use Sortable;

	protected $table = 'upload_checklists';

	protected $fillable = [
		'name',
		'file',
	];

	public $sortable = ['id', 'name', 'created_at'];
}
