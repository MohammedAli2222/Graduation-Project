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
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_instructor', 'instructor_profile_id', 'group_id');
    }
}
