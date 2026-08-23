<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParameterOption extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'parameter_id',
        'option_name',
        'option_value',
        'operator',
        'additional_price',
        'original_price',
        'selling_price',
        'supplier_id',
        'supplier_product_id',
    ];

    protected function casts(): array
    {
        return [
            'additional_price' => 'decimal:6',
            'original_price' => 'decimal:6',
            'selling_price' => 'decimal:6',
        ];
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(Parameter::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function marketplaceMappings(): HasMany
    {
        return $this->hasMany(MarketplaceOptionMapping::class);
    }
}
