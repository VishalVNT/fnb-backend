<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class purchase extends Model
{
    use HasFactory;
    protected $fillable = [
        'category_id',
        'brand_id',
        'company_id',
        'mrp',
        'qty',
        'court_fees',
        'tcs',
        'total_amount',
        'created_by',
        'invoice_no',
        'invoice_date',
		'no_btl',
		'batch_no',
		'vendor_id',
    ];
}
