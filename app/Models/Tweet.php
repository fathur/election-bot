<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tweet extends Model
{
    use HasFactory;

    protected $fillable = ['twitter_id', 'parent_id', 'account_id', 'url', 'text', 'type'];

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

    public function hasPoll()
    {
        return $this->poll == null ? false : true;
    }
}
