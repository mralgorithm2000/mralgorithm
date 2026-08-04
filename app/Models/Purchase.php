<?php

namespace App\Models;

use App\Enums\PhoneAttemptStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = [
        'unique_code',
        'plati_order_id',
        'virtual_number_id',
    ];

    public function virtualNumber(): BelongsTo
    {
        return $this->belongsTo(VirtualNumber::class);
    }

    public function phoneAttempts(): HasMany
    {
        return $this->hasMany(PhoneAttempt::class);
    }

    public function activeAttempt(): ?PhoneAttempt
    {
        return $this->phoneAttempts()
            ->where(function ($query) {
                $query->where('status', PhoneAttemptStatus::RECEIVED->value)
                    ->orWhere(function ($query) {
                        $query->where('status', PhoneAttemptStatus::WAITING->value)
                            ->where(function ($query) {
                                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                            });
                    });
            })
            ->latest('id')
            ->first();
    }

    public function latestAttempt(): ?PhoneAttempt
    {
        return $this->phoneAttempts()->latest('id')->first();
    }
}
