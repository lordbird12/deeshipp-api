<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'product';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];



    public function getImageAttribute($value)
    {
        return ($value ? url($value) : null);
    }
}
