<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poll extends Model
{
    use HasFactory;

    protected $fillable = ['start_at', 'end_at', 'total_voters', 'tweet_id', 'twitter_id'];
    
    /**
     * Get the tweet that owns the Poll
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tweet(): BelongsTo
    {
        return $this->belongsTo(Tweet::class);
    }

    /**
     * The pollChoices that belong to the Poll
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function pollChoices(): BelongsToMany
    {
        return $this->belongsToMany(PollChoice::class, 'poll_results')
            ->withPivot('total_voters');
    }

    /**
     * The reports that belong to the Poll
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function reports(): BelongsToMany
    {
        return $this->belongsToMany(Report::class, 'report_polls');
    }
}
