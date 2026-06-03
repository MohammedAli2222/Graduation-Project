<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Model;

class StudentCourseEnrollment extends Model
{
    protected $fillable = ['student_id', 'course_id', 'status', 'attempts_count'];

    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
        ];
    }


    public function studentProfile()
    {
        return $this->belongsTo(StudentProfile::class, 'student_id');
    }


    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
