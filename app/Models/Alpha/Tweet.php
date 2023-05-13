<?php

namespace App\Models\Alpha;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tweet extends Model
{
    use HasFactory;

    protected $connection = 'sqlite_alpha';
}
