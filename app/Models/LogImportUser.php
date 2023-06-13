<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogImportUser extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'log_import_user';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];

      //////////////////////////////////////// format //////////////////////////////////////

      protected function serializeDate(DateTimeInterface $date)
      {
          return $date->format('d/m/Y H:i:s');
      }

      //////////////////////////////////////// relation //////////////////////////////////////
}
