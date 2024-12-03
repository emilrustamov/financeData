<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые можно заполнять массово.
     *
     * @var array
     */
    protected $fillable = [
        'title_template',
        'icon',
        'ArticleType',
        'ArticleDescription',
        'Amount',
        'Currency',
        'original_amount',
        'original_currency',
        'Date',
        'ExchangeRate',
        'Link',
        'Object',
        'user_id',
        'cash_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cash()
    {
        return $this->belongsTo(Cash::class);
    }

}