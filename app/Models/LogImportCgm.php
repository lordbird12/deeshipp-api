<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogImportCgm extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'log_import_cgm';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];

      //////////////////////////////////////// format //////////////////////////////////////

      public function getBirthDateAttribute($value)
      {
          return ($value ? date('d/m/Y', strtotime($value)) : null);
      }

      protected function serializeDate(DateTimeInterface $date)
      {
          return $date->format('d/m/Y H:i:s');
      }

      //////////////////////////////////////// relation //////////////////////////////////////
}
