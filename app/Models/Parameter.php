<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parameter extends Model
{
    protected $fillable = [
        'title',
        'parameter_key',
        'type',
        'goods_id',
        'is_main'
    ];

    public function options(): HasMany
    {
        return $this->hasMany(ParameterOption::class);
    }

    public function marketplaceMappings(): HasMany
    {
        return $this->hasMany(MarketplaceParameterMapping::class);
    }

}
