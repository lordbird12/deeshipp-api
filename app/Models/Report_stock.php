<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report_stock extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'report_stock';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];

    //////////////////////////////////////// format //////////////////////////////////////

    public function getDateAttribute($value)
    {
        return ($value ? date('d/m/Y', strtotime($value)) : null);
    }

    public function getStatusAtAttribute($value)
    {
        return ($value ? date('d/m/Y H:i:s', strtotime($value)) : null);
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }

    //////////////////////////////////////// relation //////////////////////////////////////

    public function item_trans()
    {
        return $this->hasMany(Item_trans::class);
    }




    public function sale_order()
    {
        return $this->belongsTo(Sale_order::class);
    }

    public function lot_trans()
    {
        return $this->hasMany(Lot_trans::class);
    }

    public function doc()
    {
        return $this->belongsTo(Doc::class);
    }


}
