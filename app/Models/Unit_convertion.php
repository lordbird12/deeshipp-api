<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit_convertion extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'unit_convertion';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];

    //////////////////////////////////////// format //////////////////////////////////////

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }

    //////////////////////////////////////// relation //////////////////////////////////////

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function sale_order_lines()
    {
        return $this->hasMany(Sale_order_line::class);
    }

    //bom
    public function bom()
    {
        return $this->hasMany(Bom::class);
    }

    public function bom_lines()
    {
        return $this->hasMany(Bom_line::class);
    }

    public function job()
    {
        return $this->hasMany(Job::class);
    }

    public function item_trans()
    {
        return $this->hasMany(Item_trans::class);
    }

    public function routing_lines()
    {
        return $this->hasMany(Routing_line::class);
    }
}
