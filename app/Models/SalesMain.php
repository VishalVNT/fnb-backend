<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesMain extends Model
{
    use HasFactory;

    protected $table = 'sales_main';

	protected $fillable = [
        'invoice_no',
        'invoice_date',
        'company_id',
		'created_by',
    ];
}
 