<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_cash_id',
        'to_cash_id',
        'amount',
        'user_id',
        'note',
        'from_record_id',
        'to_record_id',
    ];

    public function fromCash()
    {
        return $this->belongsTo(Cash::class, 'from_cash_id');
    }

    public function toCash()
    {
        return $this->belongsTo(Cash::class, 'to_cash_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
