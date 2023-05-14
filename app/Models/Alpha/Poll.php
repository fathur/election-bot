<?php

namespace App\Models\Alpha;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Poll extends Model
{
    use HasFactory;

    protected $connection = 'sqlite_alpha';

    /**
     * The choices that belong to the Poll
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function choices(): BelongsToMany
    {
        return $this->belongsToMany(PollChoice::class, 'poll_results')
            ->using(PollResult::class)
            ->withPivot(['total_voter', 'id', 'created_at', 'updated_at']);
    }
}
