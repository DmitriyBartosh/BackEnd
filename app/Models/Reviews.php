<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reviews extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'expert_id',
        'transaction_id',
        'theme',
        'name',
        'link',
        'status',
        'message_failure',
        'message_after_review'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'theme' => 'string',
        'name' => 'string',
        'link' => 'string',
        'status' => 'string',
        'message_failure' => 'string',
        'message_after_review' => 'string'
    ];

    public function expert()
    {
        return $this->belongsTo(Expert::class, 'expert_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->select('id', 'name', 'email');
    }

    public function work()
    {
        return $this->belongsTo(Works::class, 'work_id');
    }
}
