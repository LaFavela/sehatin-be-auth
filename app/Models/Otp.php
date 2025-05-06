<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Otp extends Model
{
    protected $connection = 'mongodb';

    protected $primaryKey = 'token';


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'otp',
        'expires_at',
        'token'
    ];



    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
