<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale_order extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'sale_order';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];

    //////////////////////////////////////// format //////////////////////////////////////

    public function getImageAttribute($value)
    {
        return ($value ? url($value) : null);
    }

    public function getDateAttribute($value)
    {
        return ($value ? date('d/m/Y', strtotime($value)) : null);
    }

    public function getShippingDateAttribute($value)
    {
        return ($value ? date('d/m/Y', strtotime($value)) : null);
    }

    public function getAmountAttribute($value)
    {
        return ($value ? number_format($value, 2) : 0.00);
    }

    public function getTaxAmountAttribute($value)
    {
        return ($value ? number_format($value, 2) : 0.00);
    }

    public function getNetAmountAttribute($value)
    {
        return ($value ? number_format($value, 2) : 0.00);
    }

    public function getStatusAtAttribute($value)
    {
        return ($value ? date('d/m/Y H:i:s', strtotime($value)) : null);
    }

    public function getCloseAtAttribute($value)
    {
        return ($value ? date('d/m/Y H:i:s', strtotime($value)) : null);
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }

    //////////////////////////////////////// relation //////////////////////////////////////
    public function item_trans()
    {
        return $this->hasMany(Item_trans::class);
    }

    
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sale_order_lines()
    {
        return $this->hasMany(Sale_order_line::class);
    }

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    public function qty_sale_order_jobs()
    {
        return $this->hasMany(Qty_sale_order_job::class);
    }

    public function item_code()
    {
        return $this->belongsTo(Item::class,'item_id','id');
    }

    public function saleorder_id()
    {
        return $this->hasMany(Item_trans::class,'sale_order_id','id');
    }


    public  function sale ()
    {
        return $this->belongsTo(User::class);
    }

    public  function user_create ()
    {
        return $this->belongsTo(User::class,'create_by','user_id');
    }
}
