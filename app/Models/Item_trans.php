<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item_trans extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'item_trans';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];

    //////////////////////////////////////// format //////////////////////////////////////

    public function getDateAttribute($value)
    {
        return ($value ? date('d/m/Y', strtotime($value)) : null);
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }

    //////////////////////////////////////// relation //////////////////////////////////////
    public function item_type()
    {
        return $this->belongsTo(Item_type::class);
    }
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function sale_orders()
    {
        return $this->hasMany(Sale_order::class);
    }
    public function sale_order2()
    {
        return $this->belongsTo(Sale_order::class, 'sale_order_id', 'id');
    }
    public function report_stock()
    {
        return $this->belongsTo(Report_stock::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    //location
    public function location_1()
    {
        return $this->belongsTo(Location::class, 'location_1_id', 'id');
    }

    public function location_2()
    {
        return $this->belongsTo(Location::class, 'location_2_id', 'id');
    }
    //

    public function lot_trans()
    {
        return $this->hasMany(Lot_trans::class);
    }

    public function unit_convertion()
    {
        return $this->belongsTo(Unit_convertion::class);
    }

    public function qc_incoming_receive_mat_lines()
    {
        return $this->hasMany(Qc_incoming_receive_mat_line::class);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function job_trans()
    {
        return $this->belongsTo(Job_trans::class);
    }

    public function delevery_order()
    {
        return $this->belongsTo(Delevery_order::class);
    }

    public function qc()
    {
        return $this->belongsTo(Qc::class);
    }

    public function qc_defect()
    {
        return $this->belongsTo(Qc_defect::class);
    }

    public function qc_incoming_receive_mat()
    {
        return $this->belongsTo(Qc_incoming_receive_mat::class);
    }

}
