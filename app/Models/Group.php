<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Group extends Model
{
    use HasFactory; 
    protected $fillable = ['group_name', 'academic_year'];

    public function instructors()
    {
        return $this->belongsToMany(
            InstructorProfile::class,
            'group_instructor',
            'group_id',
            'instructor_profile_id'
        )->withTimestamps();
    }

    protected $casts = [
        'academic_year' => 'integer',
    ];

    public function students()
    {
        return $this->hasMany(StudentProfile::class, 'group_id');
    }
}
