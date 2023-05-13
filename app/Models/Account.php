<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = ['twitter_id', 'username', 'name'];

    public function tweets()
    {
        return $this->hasMany(Tweet::class);
    }
}
