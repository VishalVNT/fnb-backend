<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_id',
        'branch_id',
        'category_id',
        'brand_id',
        'qty',
        'physical_closing',
        'cost_price',
        'btl_selling_price',
        'peg_selling_price',
        'status',
    ];
}
