<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodDetail extends Model
{
    protected $fillable = [
        'good_id',
        'good_key',
        'good_name',
        'good_value',
    ];

    public function good(): BelongsTo
    {
        return $this->belongsTo(Good::class);
    }
}
