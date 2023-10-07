<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promocode extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'direction',
        'periodicity',
        'expired_at',
        'code',
        'status'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name' => 'string',
        'direction' => 'string',
        'periodicity' => 'integer',
        'expired_at' => 'date',
        'code' => 'string',
        'status' => 'string'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->select('id', 'name', 'email');
    }
}
