<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstructorProfile extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'phone',
        'specialty',
        'specialty_year',
        'department_id',
        'requested_department_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_instructor', 'instructor_profile_id', 'group_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function requestedDepartment()
    {
        return $this->belongsTo(Department::class, 'requested_department_id');
    }
}
