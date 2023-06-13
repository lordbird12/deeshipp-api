<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item_line extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'item_lines';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];


    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }
    public function item()
    {
        return $this->belongsTo(Item::class);
    }


    public function main_item()
    {
        return $this->belongsTo(Item::class,'main_item_id','id');
    }
}
