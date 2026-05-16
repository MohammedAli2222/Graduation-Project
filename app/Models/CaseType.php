<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseType extends Model
{
    protected $fillable = [
        'name',
        'course_id',
        'required_count',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
