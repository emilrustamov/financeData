<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Cash extends Model
{
    use HasFactory;
  

    protected $table = 'cashes';
    protected $fillable = ['title'];

   
    public function records()
    {
        return $this->hasMany(Record::class, 'cash_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'cash_user')->withTimestamps();
    }
}
