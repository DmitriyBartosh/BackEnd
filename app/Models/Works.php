<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Works extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'direction',
        'theme',
        'name',
        'link'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'direction' => 'string',
        'theme' => 'string',
        'name' => 'string',
        'link' => 'string'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->select('id', 'name', 'email');
    }
}
