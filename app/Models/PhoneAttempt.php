<?php

namespace App\Models;

use App\Enums\PhoneAttemptStatus;
use App\Enums\PurchaseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhoneAttempt extends Model
{
    protected $fillable = [
        'purchase_id',
        'provider_order_id',
        'provider',
        'phone_number',
        'country_code',
        'sms_code',
        'sold_price',
        'cost_price',
        'marketplace_fee',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'sold_price' => 'decimal:6',
        'cost_price' => 'decimal:6',
        'marketplace_fee' => 'decimal:6',
        'expires_at' => 'datetime',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function isWaiting(): bool
    {
        return $this->status === PhoneAttemptStatus::WAITING->value;
    }

    public function isReceived(): bool
    {
        return $this->status === PhoneAttemptStatus::RECEIVED->value;
    }

    public function isExpired(): bool
    {
        return $this->status === PhoneAttemptStatus::EXPIRED->value;
    }

    public function receiveCode(string $code): void
    {
        $this->update([
            'sms_code' => $code,
            'status' => PhoneAttemptStatus::RECEIVED->value,
        ]);

        $this->purchase()->update([
            'status' => PurchaseStatus::COMPLETED->value,
        ]);
    }
}
