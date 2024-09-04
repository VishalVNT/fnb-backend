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
        'transaction_type',
        'brand_id',
        'qty',
        'btl',
        'log',
        'date',
        'created_by'
    ];
}
