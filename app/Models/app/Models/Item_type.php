<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item_type extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'item_type';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];

    //////////////////////////////////////// format ///////////////////////////////////////

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }

    //////////////////////////////////////// relation //////////////////////////////////////

    public  function user_create ()
    {
        return $this->belongsTo(User::class,'create_by','user_id');
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function stock_controls()
    {
        return $this->hasMany(Stock_control::class);
    }

}
