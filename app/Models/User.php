<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;
    
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_admin',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'is_admin',
        'is_active',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function cashes()
    {
        return $this->belongsToMany(Cash::class, 'cash_user')->withTimestamps();
    }

    protected static function booted()
{
    static::deleting(function ($user) {
        if ($user->id == 1) {
            abort(403, 'Нельзя удалить главного админа.');
        }
    });
}
}