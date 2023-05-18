<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    use HasFactory;
    protected $fillable = [
        'recipe_code',
        'name',
        'category_id',
        'brand_id',
        'company_id',
        'branch_id',
        'serving_size',
        'is_cocktail',
        'created_by'
    ];
}
