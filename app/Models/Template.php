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
        'Date',
        'ExchangeRate',
        'Link',
        'Object',
    ];
}