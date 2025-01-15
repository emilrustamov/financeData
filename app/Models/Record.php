<?php

// app/Models/Record.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    use HasFactory;

    protected $table = 'records';
    protected $fillable = [
        'original_amount',
        'original_currency',
        'ArticleType',
        'ArticleDescription',
        'Amount',
        'Currency',
        'Date',
        'ExchangeRate',
        'Link',
        'Object',
        'cash_id',
        'is_debt', 
    ];

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class, 'cash_id');
    }

   
}
