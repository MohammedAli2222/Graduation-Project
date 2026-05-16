<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
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
}
