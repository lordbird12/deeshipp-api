<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bank_owner extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'bank_owner';
    protected $softDelete = true;

    //////////////////////////////////////// format //////////////////////////////////////

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }

    //////////////////////////////////////// relation //////////////////////////////////////

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
