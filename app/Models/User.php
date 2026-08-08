<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function storeProfile()
    {
        return $this->hasOne(StoreProfile::class);
    }

    public function departmentHeadProfile()
    {
        return $this->hasOne(DepartmentHeadProfile::class);
    }

    public function studentProfile()
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function instructorProfile()
    {
        return $this->hasOne(InstructorProfile::class);
    }

    // توكنات أجهزة المستخدم المستخدمة لإرسال إشعارات Firebase (قد يملك المستخدم أكثر من توكن لتعدد الأجهزة)
    public function deviceTokens()
    {
        return $this->hasMany(UserDeviceToken::class);
    }

    public function getProfileRelationName($role)
    {
        return match ($role) {
            'student' => 'studentProfile',
            'instructor' => 'instructorProfile',
            'department_head' => 'departmentHeadProfile',
            'store_owner' => 'storeProfile',
            default => null,
        };
    }
}
