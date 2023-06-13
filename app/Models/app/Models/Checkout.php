<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Checkout extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'checkouts';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];

    //////////////////////////////////////// format //////////////////////////////////////

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }


    public function sale_page_id()
    {
        return $this->belongsTo(Sale_page::class,'id');
    }
    public function item()
    {
        return $this->belongsTo(Item::class,'id');
    }
   
    public function sale_page_promotion()
    {
        return $this->hasMany(Sale_page_promotion::class,'sale_pages_id','id');
    }
}
