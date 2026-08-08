<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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

    public function purchases(): MorphMany
    {
        return $this->morphMany(Purchase::class, 'purchasable');
    }
}
