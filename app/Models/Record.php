<?php

// app/Models/Record.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Record extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'type',
        'description',
        'amount',
        'date',
        'cash_id',
        'object_id',
        'category_id',
        'project_id',
        'user_id',
    ];

    public function cash()
    {
        return $this->belongsTo(Cash::class, 'cash_id');
    }

    public function project()
    {
        return $this->belongsTo(Projects::class, 'project_id');
    }

    public function object()
    {
        return $this->belongsTo(Objects::class, 'object_id');
    }

    public function category()
    {
        return $this->belongsTo(ObjectCategories::class, 'category_id');
    }

    public function transferFrom()
    {
        return $this->hasOne(Transfer::class, 'from_record_id');
    }

    public function transferTo()
    {
        return $this->hasOne(Transfer::class, 'to_record_id');
    }
}
