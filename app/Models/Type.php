<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Type extends Model
{
    protected $fillable = [
        'title',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(TypeItem::class);
    }

    public function goods(): HasMany
    {
        return $this->hasMany(Good::class);
    }
}
