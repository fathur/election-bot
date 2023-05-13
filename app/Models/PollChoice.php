<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PollChoice extends Model
{
    use HasFactory;

    /**
     * The polls that belong to the PollChoice
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function polls(): BelongsToMany
    {
        return $this->belongsToMany(Poll::class, 'poll_results')
            ->withPivot('total_voters');
    }

    /**
     * The reports that belong to the PollChoice
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function reports(): BelongsToMany
    {
        return $this->belongsToMany(Report::class, 'report_choices')
            ->withPivot('total_voters');
    }
}
