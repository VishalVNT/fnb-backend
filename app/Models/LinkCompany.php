<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LinkCompany extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_id',
        'link_company_id',
        'created_by',
        'status',
    ];
}
