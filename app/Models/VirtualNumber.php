<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualNumber extends Model
{
    protected $fillable = [
        'country',
        'original_price',
        'source',
        'type',
        'plati_id',
    ];

    protected $table = 'virtual_numbers';
}
