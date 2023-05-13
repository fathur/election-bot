<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    /**
     * The polls that belong to the Report
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function polls(): BelongsToMany
    {
        return $this->belongsToMany(Poll::class, 'report_polls');
    }

    /**
     * The choices that belong to the Report
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function choices(): BelongsToMany
    {
        return $this->belongsToMany(PollChoice::class, 'report_choices')
            ->withPivot('total_voters');
    }
}
