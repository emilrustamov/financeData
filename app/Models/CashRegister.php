<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashRegister extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'balance',
        'date',
        'cash_id', 
    ];

    
    public function cash()
    {
        return $this->belongsTo(Cash::class, 'cash_id');
    }

}

