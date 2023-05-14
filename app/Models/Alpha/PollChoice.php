<?php

namespace App\Models\Alpha;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PollChoice extends Model
{
    use HasFactory;

    protected $connection = 'sqlite_alpha';

    /**
     * The polls that belong to the PollChoice
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function polls(): BelongsToMany
    {
        return $this->belongsToMany(Poll::class, 'poll_results')
            ->using(PollResult::class)
            ->withPivot(['total_voter', 'id', 'created_at', 'updated_at']);

    }
}
