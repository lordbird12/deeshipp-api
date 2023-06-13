<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubAgencyCommand extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'sub_agency_command';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];

    //////////////////////////////////////// format //////////////////////////////////////

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }

    //////////////////////////////////////// relation //////////////////////////////////////

    public function agency_command()
    {
        return $this->belongsTo(AgencyCommand::class, 'agency_command_id', 'agency_command_id');
    }

    public function affiliation()
    {
        return $this->hasMany(Affiliation::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function members()
    {
        return $this->hasMany(Member::class);
    }

}
