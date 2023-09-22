<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscribe extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'transaction_id',
        'plan_id',
        'transaction_status',
        'started_at',
        'expired_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'transaction_id' => 'string',
        'transaction_status' => 'string',
        'started_at' => 'date',
        'expired_at' => 'date',
        'periodicity_type' => 'string'
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
