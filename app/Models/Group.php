<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{

    protected $fillable = ['group_name'];


    public function instructors()
    {
        return $this->belongsToMany(
            InstructorProfile::class,
            'group_instructor',
            'group_id',
            'instructor_profile_id'
        )->withTimestamps();
    }

    public function students()
    {
        return $this->hasMany(StudentProfile::class, 'group_id');
    }
}
