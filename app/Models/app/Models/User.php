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
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    
    public  function user_create ()
    {
        return $this->belongsTo(User::class,'create_by','user_id');
    }
  
    // public function affiliation()
    // {
    //     return $this->belongsTo(Affiliation::class, 'affiliation_id', 'affiliation_id');
    // }


    //prefix
    public function position()
    {
        //return $this->belongsTo(Position::class, 'position_id', 'position_id');
        //return $this->hasOne(Position::class,'id','id');
        return $this->belongsTo(Position::class,);
    }
//     public function prefix_type()
//     {
//         return $this->belongsTo(PrefixType::class, 'prefix_type_id', 'prefix_type_id');
//     }

//     public function prefix()
//     {
//         return $this->belongsTo(Prefix::class, 'prefix_id', 'prefix_id');
//     }

 }
