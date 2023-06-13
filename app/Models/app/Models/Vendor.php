<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'vendor';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];

    //////////////////////////////////////// format //////////////////////////////////////

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }

    //////////////////////////////////////// relation //////////////////////////////////////
    public  function user_create ()
    {
        return $this->belongsTo(User::class,'create_by','user_id');
    }
    
    public function item_trans()
    {
        return $this->hasMany(Item_trans::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function delivered_by()
    {
        return $this->belongsTo(Delivered_by::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
