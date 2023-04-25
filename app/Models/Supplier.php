<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_id',
        'name',
        'contact_person',
        'mail',
        'mobile',
        'landline',
        'license_no',
        'address',
        'pincode',
        'city',
        'created_by',
    ];
}
