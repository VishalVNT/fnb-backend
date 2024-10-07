<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_id',
        'company_to_id',
        'new_company_name',
        'transaction_type',
        'brand_id',
        'qty',
        'btl',
        'log',
        'date',
        'remark',
        'status',
        'created_by'
    ];
}
