<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntryreviewAnswers extends Model
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
        'status',
        'questions',
        'answers',
        'result'
    ];

        /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'string',
        'questions' => 'json',
        'answers' => 'json',
        'result' => 'string'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->select('id', 'name', 'email', 'telegram_chat');
    }
}
