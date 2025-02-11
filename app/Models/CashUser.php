<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashUser extends Model
{
    use HasFactory;
    use SoftDeletes;


    protected $fillable = ['cash_id', 'user_id'];

    public function cash()
    {
        return $this->belongsTo(Cash::class, 'cash_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
