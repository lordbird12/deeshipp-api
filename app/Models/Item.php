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
    public  function user_create()
    {
        return $this->belongsTo(User::class, 'create_by', 'user_id');
    }

    public function item_type()
    {
        return $this->belongsTo(Item_type::class);
    }

    public function item_trans()
    {
        return $this->hasMany(Item_trans::class);
    }

    public function item_images()
    {
        return $this->hasMany(Item_image::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function item_attributes()
    {
        return $this->hasMany(Item_attribute::class);
    }

    public function item_attribute_seconds()
    {
        return $this->hasMany(Item_attribute_second::class);
    }




    //item_line
    public function item_lines()
    {
        return $this->hasMany(Item_line::class, 'item_id', 'id');
    }


    //sale order line
    public function sale_order_lines()
    {
        return $this->hasMany(Sale_order_line::class);
    }


    //vendor
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
    //



}
