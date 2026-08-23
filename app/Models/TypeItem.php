<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypeItem extends Model
{
    protected $fillable = [
        'type_id',
        'type_key',
        'type_name',
        'type',
        'options',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }
}
