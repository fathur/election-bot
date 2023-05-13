<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tweet extends Model
{
    use HasFactory;

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the poll associated with the Tweet
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function poll(): HasOne
    {
        return $this->hasOne(Poll::class);
    }
}
