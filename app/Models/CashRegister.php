<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    use HasFactory;
    protected $table = 'cash_register';
    protected $fillable = [
        'ArticleType',
        'Amount',
        'Currency',
        'initial_balance',
        'Date',
    ];
}
