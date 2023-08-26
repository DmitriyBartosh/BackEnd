<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'vkontakte_id',
        'yandex_id',
        'google_id',
        'direction'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function allworks()
    {
        return $this->hasMany(Works::class)->select('id', 'direction', 'theme', 'name', 'link');
    }

    public function expert()
    {
        return $this->hasOne(Expert::class);
    }

    public function work_under_review()
    {
        return $this->hasMany(Reviews::class)->select('id', 'expert_id', 'theme', 'name', 'link', 'status', 'message_failure', 'message_after_review');
    }
}
