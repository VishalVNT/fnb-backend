<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'license_name',
        'license_no',
        'pan_no',
        'gst_no',
        'address',
        'city',
        'pincode',
    ];
}
