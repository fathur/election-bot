<?php

namespace App\Models\Alpha;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PollResult extends Pivot
{
    protected $connection = 'sqlite_alpha';

}
