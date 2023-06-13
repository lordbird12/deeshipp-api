<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'member';
    protected $softDelete = true;

    protected $hidden = ['password', 'deleted_at'];

    //////////////////////////////////////// format //////////////////////////////////////

    public function getImageAttribute($value)
    {
        return ($value ? url($value) : null);
    }

    public function getBirthDateAttribute($value)
    {
        return ($value ? date('d/m/Y', strtotime($value)) : null);
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }

    //////////////////////////////////////// relation //////////////////////////////////////

    public function agency_command()
    {
        return $this->belongsTo(AgencyCommand::class, 'agency_command_id', 'agency_command_id');
    }

    public function sub_agency_command()
    {
        return $this->belongsTo(SubAgencyCommand::class, 'sub_agency_command_id', 'sub_agency_command_id');
    }

    public function affiliation()
    {
        return $this->belongsTo(Affiliation::class, 'affiliation_id', 'affiliation_id');
    }

    //prefix
    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id', 'position_id');
    }

    public function prefix_type()
    {
        return $this->belongsTo(PrefixType::class, 'prefix_type_id', 'prefix_type_id');
    }

    public function prefix()
    {
        return $this->belongsTo(Prefix::class, 'prefix_id', 'prefix_id');
    }

    //course
    public function course_group_members()
    {
        return $this->hasMany(CourseGroupMember::class);
    }

    //m to m
    public function course_groups()
    {
        return $this->belongsToMany(CourseGroup::class, 'course_group_member', 'member_id', 'course_group_id', 'member_id', 'course_group_id');
    }

}
