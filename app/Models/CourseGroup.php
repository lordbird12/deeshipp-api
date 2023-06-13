<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseGroup extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'course_group';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];

    //////////////////////////////////////// format //////////////////////////////////////

    public function getDateStartAttribute($value)
    {
        return ($value ? date('d/m/Y', strtotime($value)) : null);
    }

    public function getDateStopAttribute($value)
    {
        return ($value ? date('d/m/Y', strtotime($value)) : null);
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d/m/Y H:i:s');
    }

    //////////////////////////////////////// relation //////////////////////////////////////

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    public function course_group_quotas()
    {
        return $this->hasMany(CourseGroupQuota::class);
    }

    public function course_group_members()
    {
        return $this->hasMany(CourseGroupMember::class);
    }

    //m to m
    public function members()
    {
        return $this->belongsToMany(Member::class, 'course_group_member', 'course_group_id', 'member_id', 'id', 'member_id');
    }

}
