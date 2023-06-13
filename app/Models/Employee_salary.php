<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee_salary extends Model
{
    use HasFactory;

    use SoftDeletes;
    protected $table = 'employee_salaries';
    protected $softDelete = true;
    protected $hidden = ['deleted_at'];



    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }
   
}
