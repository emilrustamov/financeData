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
        'Type',
        'ArticleType',
        'ArticleDescription',
        'Amount',
        'Currency',
        'Date',
        'ExchangeRate',
        'Link',
        'Object',
    ];
}
