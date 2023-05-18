<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseList extends Model
{
    use HasFactory;
    protected $table = 'purchase_lists';
    public $timestamps = false;
    protected $fillable = [
        'company_id',
        'court_fees',
        'tcs',
        'total_amount',
        'created_by',
        'invoice_no',
        'total_item',
        'invoice_date',
        'batch_no',
        'discount',
        'vat',
        'vendor_id',
        'isInvoice',
        'status',
    ];
}
