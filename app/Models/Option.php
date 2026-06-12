<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    protected $table = 'options';

    protected $fillable = [
        'plati_id',
        'option_id',
        'title',
        'type'
    ];
}
