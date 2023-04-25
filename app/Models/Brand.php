<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'short_name',
        'btl_size',
        'peg_size',
        'no_peg',
        'category_id',
        'created_by'
    ];
}
