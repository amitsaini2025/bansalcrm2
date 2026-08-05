<?php
namespace App\Models;

use Kyslik\ColumnSortable\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckinLog extends Model
{
    use Sortable;

	protected $fillable = [
        'id', 'client_id', 'user_id', 'visit_purpose', 'office',
        'contact_type', 'status', 'date', 'sesion_start', 'sesion_end',
        'wait_time', 'attend_time', 'wait_type',
        'created_at', 'updated_at'
    ];
	
	public $sortable = ['id', 'created_at', 'updated_at'];

    /**
     * Get the office (branch) where this check-in occurred.
     * Column is 'office' (not office_id) - stores branch id.
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'office', 'id');
    }
	
	/**
     * Get the client/lead for this check-in (Admin table holds both).
     */
    public function client()
    {
        return $this->belongsTo('App\Models\Admin', 'client_id');
    }
    
    /**
     * Get the assignee for this check-in
     */
    public function assignee()
    {
        return $this->belongsTo('App\Models\Staff', 'user_id');
    }
    
    /**
     * Get the history entries for this check-in
     */
    public function histories()
    {
        return $this->hasMany('App\Models\CheckinHistory', 'checkin_id');
    }

    /**
     * Sidebar / poll badge: waiting (status=0) count for the given staff user.
     * Super admin + reception see the full floor; others see only their assignee queue.
     * Does not change waiting-list page queries (shared queue UX unchanged).
     */
    public static function waitingCountForUser($user): int
    {
        if (!$user) {
            return 0;
        }

        $receptionId = (int) config('constants.reception_user_id', 0);
        $seesAll = ((int) $user->role === 1)
            || ($receptionId > 0 && (int) $user->id === $receptionId);

        if ($seesAll) {
            return (int) static::where('status', 0)->count();
        }

        return (int) static::where('user_id', $user->id)->where('status', 0)->count();
    }
}