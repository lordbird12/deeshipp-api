<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bank extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'banks';
    protected $softDelete = true;

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

    public function user_banks()
    {
        return $this->hasMany(User_bank::class);
    }
}
