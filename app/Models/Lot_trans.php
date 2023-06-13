<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lot_trans extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'lot_trans';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];

    //////////////////////////////////////// format //////////////////////////////////////

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }

    //////////////////////////////////////// relation //////////////////////////////////////

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function item_trans()
    {
        return $this->belongsTo(Item_trans::class);
    }

    public function location_1()
    {
        return $this->belongsTo(Location::class);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

}
