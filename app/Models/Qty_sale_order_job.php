<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Qty_sale_order_job extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'qty_sale_order_job';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];

     //////////////////////////////////////// relation //////////////////////////////////////

     public function sale_order()
     {
         return $this->belongsTo(Sale_order::class);
     }

     public function job()
     {
         return $this->belongsTo(Job::class);
     }


}
