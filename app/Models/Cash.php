<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cash extends Model
{
    use HasFactory;

    protected $table = 'cash';

    protected $fillable = ['title', 'currency_id'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'cash_user');
    }

    public function currency()
    {
        return $this->belongsTo(ExchangeRate::class, 'currency_id');
    }

}
