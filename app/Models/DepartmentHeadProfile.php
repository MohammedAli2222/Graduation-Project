<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentHeadProfile extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'department_id',
        'first_name',
        'last_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
