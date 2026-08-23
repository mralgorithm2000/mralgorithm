<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsMarketplaceMapping extends Model
{
    protected $fillable = [
        'good_id',
        'marketplace',
        'marketplace_product_id',
    ];

    protected function casts(): array
    {
        return [
            'marketplace_product_id' => 'integer',
        ];
    }

    public function good(): BelongsTo
    {
        return $this->belongsTo(Good::class);
    }
}
