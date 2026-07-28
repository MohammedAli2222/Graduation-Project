<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'department_id',
        'year',
        'semester',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function caseTypes()
    {
        return $this->hasMany(CaseType::class);
    }

    public function enrollments()
    {
        return $this->hasMany(StudentCourseEnrollment::class, 'course_id');
    }

    public function students()
    {
        return $this->belongsToMany(StudentProfile::class, 'student_course_enrollments', 'course_id', 'student_id')
            ->withPivot(['status', 'attempts_count'])
            ->withTimestamps()
            ->using(StudentCourseEnrollment::class);
    }
}
