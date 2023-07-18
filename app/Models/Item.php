<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'item';
    protected $softDelete = true;

    protected $fillable = ['item_id', 'date', 'stock', 'qty', 'exc', 'adj_qa', 'balance', 'type', 'create_by'];

    protected $hidden = ['deleted_at'];

    //////////////////////////////////////// format //////////////////////////////////////

    public function getImageAttribute($value)
    {
        return ($value ? url($value) : null);
    }

    public function getPriceAttribute($value)
    {
        return ($value ? number_format($value, 2) : 0.00);
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }

    //////////////////////////////////////// relation //////////////////////////////////////
    public  function user_create ()
    {
        return $this->belongsTo(User::class,'create_by','user_id');
    }

    public function item_type()
    {
        return $this->belongsTo(Item_type::class);
    }

    public function item_trans()
    {
        return $this->hasMany(Item_trans::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }



    //unit
    public function unit_store()
    {
        return $this->belongsTo(Unit::class, 'unit_store_id', 'id');
    }

    public function unit_buy()
    {
        return $this->belongsTo(Unit::class, 'unit_buy_id', 'id');
    }

    public function unit_sell()
    {
        return $this->belongsTo(Unit::class, 'unit_sell_id', 'id');
    }
    //



     //item_line
     public function item_line()
     {
         return $this->belongsTo(Item_line::class,'item_id','id');
     }

     public function main_itemLine()
     {
         return $this->hasMany(Item_line::class,'main_item_id','id');
     }




    //spare
    public function Spare_type()
    {
        return $this->belongsTo(Spare_type::class);
    }
    //

    //sale order line
    public function sale_order_lines()
    {
        return $this->hasMany(Sale_order_line::class);
    }

    public function forcash_lines()
    {
        return $this->hasMany(Forcash_line::class);
    }

    //bom
    public function bom()
    {
        return $this->hasMany(Bom::class);
    }

    public function bom_lines()
    {
        return $this->hasMany(Bom_line::class);
    }

    //job
    public function job_trans()
    {
        return $this->hasMany(Job_trans::class);
    }

    public function wips()
    {
        return $this->hasMany(Wip::class);
    }

    //mrp
    public function pre_material_request_lines()
    {
        return $this->hasMany(Pre_material_request_line::class);
    }

    public function material_request_report_lines()
    {
        return $this->hasMany(Material_request_report_line::class);
    }

    //qc
    public function qc_outgoing_lines()
    {
        return $this->hasMany(Qc_outgoing_line::class);
    }

     //vendor
     public function vendor()
     {
         return $this->belongsTo(Vendor::class);
     }
     //



}
