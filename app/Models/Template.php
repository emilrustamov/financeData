<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Template extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title_template',
        'icon',
        'type',
        'description',
        'amount',
        'date',
        'object_id',
        'project_id',
        'category_id',
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