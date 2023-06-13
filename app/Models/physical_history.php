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
        'status',
    ];
}
