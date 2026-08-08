<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'total_chairs',
        'description',
    ];

    protected $casts = [
        'total_chairs' => 'integer',
    ];

    public function departmentHeadProfile()
    {
        return $this->hasOne(DepartmentHeadProfile::class, 'user_id');
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function caseTypes()
    {
        return $this->hasManyThrough(CaseType::class, Course::class);
    }

    public function diagnoses()
    {
        return $this->hasManyThrough(PatientDiagnose::class, CaseType::class);
    }
}
