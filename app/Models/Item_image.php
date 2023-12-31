<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item_image extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'item_images';
    protected $softDelete = true;


    protected $hidden = ['deleted_at'];

    //////////////////////////////////////// format //////////////////////////////////////

    public function getImageAttribute($value)
    {
        return ($value ? url($value) : null);
    }


    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }

    //////////////////////////////////////// relation //////////////////////////////////////

    public  function item()
    {
        return $this->belongsTo(Item::class);
    }
}
