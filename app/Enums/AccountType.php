<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum AccountType
{
    case CANDIDATE;
    case MEDIA;
    case PARTY;
    case INFLUENCER;

    case SENTIMENT; // Not used for poll

    public function text()
    {
        return Str::lower($this->name);
    }
}
