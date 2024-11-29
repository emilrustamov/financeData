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
        'user_id', // Добавлено
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function records()
    {
        return $this->hasMany(Record::class, 'cash_id');
    }
    
}

