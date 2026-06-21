<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    protected $fillable = [
        'user_id',
        'group_id',
        'phone',
        'exam_number',
        'university',
        'academic_year',
        'semester',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function enrollments()
    {
        return $this->hasMany(StudentCourseEnrollment::class, 'student_id');
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'student_course_enrollments', 'student_id', 'course_id')
            ->withPivot(['status', 'attempts_count'])
            ->withTimestamps()
            ->using(StudentCourseEnrollment::class);
    }
}
