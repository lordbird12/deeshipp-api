<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item_attribute extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'item_attribute';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];


    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }

    ///
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function item_attribute_seconds()
    {
        return $this->hasMany(Item_attribute_second::class);
    }
}
