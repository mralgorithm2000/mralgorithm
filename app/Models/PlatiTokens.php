<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatiTokens extends Model
{
    protected $table = "plati_tokens";

    protected $fillable = [
        'token',
        'expire_time'
    ];
}
