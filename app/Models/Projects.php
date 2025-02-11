<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Projects extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['title', 'description', 'users'];

    protected $casts = [
        'users' => 'array'
    ];

    public function records()
    {
        return $this->hasMany(Record::class, 'project_id');
    }
}
