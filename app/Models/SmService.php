<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmService extends Model
{
    protected $table = "sm_services";

    protected $fillable = [
        'random_id',
        'api_id',
        'type',
        'origin',
        'name',
        'sm',
        'min',
        'max'
    ];
}
