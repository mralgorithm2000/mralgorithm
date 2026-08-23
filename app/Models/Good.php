<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Good extends Model
{
    protected $fillable = [
        'name',
        'type_id',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(GoodDetail::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function marketplaceMappings(): HasMany
    {
        return $this->hasMany(GoodsMarketplaceMapping::class);
    }
}
