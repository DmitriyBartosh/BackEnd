<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DesignExpert extends Model
{
    use HasFactory;

    protected $table = 'designexperts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'status',
        'timetowork',
        'logo',
        'poster',
        'socialmedia',
        'polygraphy'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        'timetowork' => 'string',
        'logo' => 'integer',
        'poster' => 'integer',
        'socialmedia' => 'integer',
        'polygraphy' => 'integer'
    ];
}
