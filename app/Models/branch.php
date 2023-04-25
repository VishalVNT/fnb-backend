<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class branch extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_id',
        'name',
        'address',
        'city',
        'pincode',
        'created_by'
    ];
}
