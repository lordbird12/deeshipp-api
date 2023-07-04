<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User_address_sent extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'user_address_sent';
    protected $softDelete = true;

    //////////////////////////////////////// format //////////////////////////////////////

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }

    //////////////////////////////////////// relation //////////////////////////////////////

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
