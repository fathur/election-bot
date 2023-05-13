<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tweet extends Model
{
    use HasFactory;

    protected $fillable = ['twitter_id', 'parent_id', 'account_id', 'url', 'text', 'type', 'alpha_tweet_id', 'alpha_poll_tweet_id'];

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
        return self::where('parent_id', $this->id)
            ->where('type', 'poll')
            ->exists();
    }
}
