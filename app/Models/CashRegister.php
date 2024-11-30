<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    use HasFactory;

    protected $table = 'cash_register';
    protected $fillable = [
        'balance',
        'Date',
        'cash_id', // Теперь привязываем к конкретной кассе
    ];

    // Связь с кассой
    public function cash()
    {
        return $this->belongsTo(Cash::class, 'cash_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function records()
    {
        return $this->hasMany(Record::class, 'cash_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'cash_user', 'cash_id', 'user_id');
    }

}

