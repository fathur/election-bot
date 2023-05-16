<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum ReportInterval
{
    case DAILY;
    case WEEKLY;
    case MONTHLY;
    case YEARLY;

    public function text()
    {
        return Str::lower($this->name);
    }
}
