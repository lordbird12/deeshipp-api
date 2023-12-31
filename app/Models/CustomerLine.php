<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerLine extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'customer_lines';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];


    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }

   
}
