<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sales extends Model
{
    use HasFactory;
	protected $fillable = [
        'company_id',
        'brand_id',
		'category_id',
        'branch_id',
        'qty',
        'sale_price',
        'sales_type',
		'discount',
		'final_amount',
		'description',
		'created_by',
		'sale_date',
		'no_btl',
		'no_peg'
    ];
}
