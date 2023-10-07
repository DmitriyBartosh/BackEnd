<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expert extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'avatar',
        'name',
        'about',
        'slug',
        'direction',
        'price',
        'status',
        'backtowork'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        'avatar' => 'string',
        'name' => 'string',
        'about' => 'string',
        'slug' => 'string',
        'direction' => 'string',
        'price' => 'json',
        'backtowork' => 'date'
    ];
}
