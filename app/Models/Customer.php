<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'customer';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];

    //////////////////////////////////////// format //////////////////////////////////////

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }

    //////////////////////////////////////// relation //////////////////////////////////////
    public  function user_create ()
    {
        return $this->belongsTo(User::class,'create_by','user_id');
    }
   

    public function main_customerLine()
    {
        return $this->hasMany(CustomerLine::class,'customer_id','id');
    }



    public function item_trans()
    {
        return $this->hasMany(Item_trans::class);
    }

    public function sale_orders()
    {
        return $this->hasMany(Sale_order::class);
    }

    public function forcashs()
    {
        return $this->hasMany(Forcash::class);
    }

    public function qc_outgoings()
    {
        return $this->hasMany(Qc_outgoing::class);
    }
}
