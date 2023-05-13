<?php

namespace App\Models\Alpha;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PollChoice extends Model
{
    use HasFactory;

    protected $connection = 'sqlite_alpha';
}
