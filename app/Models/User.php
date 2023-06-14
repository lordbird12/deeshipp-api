<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mpdf\Tag\Br;

class User extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'users';
    protected $softDelete = true;

    protected $hidden = ['password', 'deleted_at'];

    //////////////////////////////////////// format //////////////////////////////////////

    public function getImageAttribute($value)
    {
        return ($value ? url($value) : null);
    }

    public function getImageSignatureAttribute($value)
    {
        return ($value ? url($value) : null);
    }


    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }

    //////////////////////////////////////// relation //////////////////////////////////////

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    public  function user_create()
    {
        return $this->belongsTo(User::class, 'create_by', 'user_id');
    }

    public  function user_ref()
    {
        return $this->belongsTo(User::class, 'user_ref_id', 'id');
    }

    public function user_pages()
    {
        return $this->hasMany(User_page::class);
    }

    public function user_banks()
    {
        return $this->hasMany(User_bank::class);
    }


}
