<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreProfile extends Model
{
    protected $fillable = [
        'user_id',
        'store_name',
        'store_phone',
        'store_address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
