<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item_trans extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'item_trans';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];

    //////////////////////////////////////// format //////////////////////////////////////

    public function getDateAttribute($value)
    {
        return ($value ? date('d/m/Y', strtotime($value)) : null);
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }

    //////////////////////////////////////// relation //////////////////////////////////////
    public function item_type()
    {
        return $this->belongsTo(Item_type::class);
    }
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function item_attribute()
    {
        return $this->belongsTo(Item_attribute::class);
    }

    public function item_attribute_second()
    {
        return $this->belongsTo(Item_attribute_second::class);
    }


    public function sale_order()
    {
        return $this->belongsTo(Sale_order::class, 'sale_order_id', 'id');
    }
    public function report_stock()
    {
        return $this->belongsTo(Report_stock::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

}
