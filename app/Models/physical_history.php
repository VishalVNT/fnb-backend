<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class physical_history extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_id',
        'brand_id',
        'qty',
        'date',
        'store_btl',
        'store_peg',
        'bar1_btl',
        'bar1_peg',
        'bar2_btl',
        'bar2_peg',
        'status',
    ];
}
