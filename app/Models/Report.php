<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = ['interval', 'start_at', 'end_at', 'total_voters', 'resume', 'total_polls'];

    protected $casts = [
        'start_at'  => 'datetime',
        'end_at'  => 'datetime',
        'total_voters'  => 'integer',
    ];

    /**
     * The polls that belong to the Report
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function polls(): BelongsToMany
    {
        return $this->belongsToMany(Poll::class, 'report_polls')
            ->withTimestamps();;
    }

    /**
     * The choices that belong to the Report
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function choices(): BelongsToMany
    {
        return $this->belongsToMany(PollChoice::class, 'report_choices')
            ->withPivot('total_voters')
            ->withTimestamps();;
    }
}
