<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class VirtualNumber extends Model
{
    protected $fillable = [
        'country',
        'original_price',
        'source',
        'type',
        'plati_id',
        'service_id',
        'country_id',
        'country_code',
    ];

    protected $table = 'virtual_numbers';

    public function purchases(): MorphMany
    {
        return $this->morphMany(Purchase::class, 'purchasable');
    }
}
