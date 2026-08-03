<?php
namespace App\Models;

use Kyslik\ColumnSortable\Sortable;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
	use Sortable;
	
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
	
	
	protected $fillable = [
        'id', 'customer_id', 'created_at', 'updated_at'
    ];
  
	public $sortable = ['id', 'created_at', 'updated_at'];
 
	/**
     * Creator/Staff (Admin) associated with the invoice.
     */
	public function creator()
    {
        return $this->belongsTo('App\Models\Admin', 'user_id', 'id');
    }

    /** @deprecated Use creator() instead. */
	public function user()
    {
        return $this->belongsTo('App\Models\Admin', 'user_id', 'id');
    }
	
	public function company()
    {
        return $this->belongsTo('App\Models\Admin','user_id','id');
    }
	
	public function staff()
    {
        return $this->belongsTo('App\Models\Admin','seller_id','id');
    }
	
	public function customer()
    {
        return $this->belongsTo('App\Models\Admin','client_id','id');
    }
	public function invoicedetail() 
    {
        return $this->hasMany('App\Models\InvoiceDetail','invoice_id','id');
    }
	
	public function invoiceDetails() 
    {
        return $this->hasMany('App\Models\InvoiceDetail','invoice_id','id');
    }
	
	public function invoicePayments() 
    {
        return $this->hasMany('App\Models\InvoicePayment','invoice_id','id');
    }

	public function application()
	{
		return $this->belongsTo(Application::class, 'application_id', 'id');
    }

	/**
	 * Shared paid-list / export fee totals so UI and CSV/XLSX cannot diverge (INV-7).
	 * Net Fee Paid to Partner = total_fee - (comm + tax + bonus).
	 *
	 * @param  iterable<\App\Models\InvoiceDetail|object>  $invoiceitemdetails
	 * @return array{netamount: float, coom_amt: float, total_fee: float, tax_amt: float, bonus_amt: float, feepaid: float}
	 */
	public static function sumLineFeeTotals(iterable $invoiceitemdetails): array
	{
		$netamount = 0.0;
		$coom_amt = 0.0;
		$total_fee = 0.0;
		$tax_amt = 0.0;
		$bonus_amt = 0.0;

		foreach ($invoiceitemdetails as $invoiceitemdetail) {
			$netamount += (float) ($invoiceitemdetail->netamount ?? 0);
			$coom_amt += (float) ($invoiceitemdetail->comm_amt ?? 0);
			$total_fee += (float) ($invoiceitemdetail->total_fee ?? 0);
			$tax_amt += (float) ($invoiceitemdetail->tax_amount ?? 0);
			$bonus_amt += (float) ($invoiceitemdetail->bonus_amount ?? 0);
		}

		return [
			'netamount' => $netamount,
			'coom_amt' => $coom_amt,
			'total_fee' => $total_fee,
			'tax_amt' => $tax_amt,
			'bonus_amt' => $bonus_amt,
			'feepaid' => $total_fee - ($coom_amt + $tax_amt + $bonus_amt),
		];
	}
}
