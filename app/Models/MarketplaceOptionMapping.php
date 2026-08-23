<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceOptionMapping extends Model
{
    protected $fillable = [
        'marketplace',
        'parameter_option_id',
        'marketplace_option_id',
    ];

    protected function casts(): array
    {
        return [
            'marketplace_option_id' => 'integer',
        ];
    }

    public function parameterOption(): BelongsTo
    {
        return $this->belongsTo(ParameterOption::class);
    }
}
