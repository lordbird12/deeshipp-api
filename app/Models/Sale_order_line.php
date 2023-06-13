<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale_order_line extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'sale_order_line';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];

    //////////////////////////////////////// format //////////////////////////////////////

    public function getUnitPriceAttribute($value)
    {
        return ($value ? number_format($value, 2) : 0.00);
    }

    public function getTotalPriceAttribute($value)
    {
        return ($value ? number_format($value, 2) : 0.00);
    }

    public function getDiscountAttribute($value)
    {
        return ($value ? number_format($value, 2) : 0.00);
    }

    public function getAmountAttribute($value)
    {
        return ($value ? number_format($value, 2) : 0.00);
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }

    //////////////////////////////////////// relation //////////////////////////////////////

    public function sale_order()
    {
        return $this->belongsTo(Sale_order::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function unit_convertion()
    {
        return $this->belongsTo(Unit_convertion::class);
    }

}
