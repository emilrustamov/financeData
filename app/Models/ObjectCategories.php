<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ObjectCategories extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['title', 'users'];
    protected $casts = [
        'users' => 'array',
    ];

    public function objects()
    {
        return $this->hasMany(Objects::class);
    }
}
