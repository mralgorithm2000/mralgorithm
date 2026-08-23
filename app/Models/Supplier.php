<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'title',
        'website_url',
        'status',
    ];

    public function goods(): HasMany
    {
        return $this->hasMany(Good::class);
    }
}
