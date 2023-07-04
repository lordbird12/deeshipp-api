<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivered_by extends Model
{
    use HasFactory;
    protected $table = 'delivered_by';
    protected $softDelete = true;
    protected $hidden = ['deleted_at'];


    public function getImageAttribute($value)
    {
        return ($value ? url($value) : null);
    }
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }
    public  function user_create ()
    {
        return $this->belongsTo(User::class,'create_by','user_id');
    }

    public  function user()
    {
        return $this->belongsTo(Delivered_by::class);
    }
}
