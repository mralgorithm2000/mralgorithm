<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceParameterMapping extends Model
{
    protected $fillable = [
        'marketplace',
        'marketplace_parameter_id',
        'parameter_id',
    ];

    protected function casts(): array
    {
        return [
            'marketplace_parameter_id' => 'integer',
        ];
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(Parameter::class);
    }
}
