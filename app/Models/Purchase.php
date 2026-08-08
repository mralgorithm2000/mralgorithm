<?php

namespace App\Models;

use App\Enums\PhoneAttemptStatus;
use App\Enums\PurchaseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Purchase extends Model
{
    protected $fillable = [
        'unique_code',
        'purchasable_id',
        'purchasable_type',
        'marketplace',
        'external_order_id',
        'unique_code',
        'sold_price',
        'cost_price',
        'marketplace_fee',
        'refunded_amount',
        'status',
    ];

    protected $casts = [
        'sold_price' => 'decimal:6',
        'cost_price' => 'decimal:6',
        'marketplace_fee' => 'decimal:6',
        'refunded_amount' => 'decimal:6',
    ];

    public function purchasable(): MorphTo
    {
        return $this->morphTo();
    }

    public function virtualNumber(): MorphTo
    {
        return $this->morphTo('purchasable');
    }

    public function phoneAttempts(): HasMany
    {
        return $this->hasMany(PhoneAttempt::class);
    }

    public function refundRequest(): HasOne
    {
        return $this->hasOne(RefundRequest::class);
    }

    public function activeAttempt(): ?PhoneAttempt
    {
        return $this->phoneAttempts()
            ->where(function ($query) {
                $query->where('status', PhoneAttemptStatus::RECEIVED->value)
                    ->orWhere(function ($query) {
                        $query->where('status', PhoneAttemptStatus::WAITING->value)
                            ->where(function ($query) {
                                $query->where('expires_at', '>', now());
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

    public function unexpiredAttempt(): ?PhoneAttempt
    {
        return $this->phoneAttempts()
            ->where('status', PhoneAttemptStatus::WAITING->value)
            ->where(function ($query) {
                $query->whereNull('sms_code')->orWhere('sms_code', '');
            })
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
    }

    public function hasReceivedCode(): bool
    {
        return $this->phoneAttempts()
            ->where(function ($query) {
                $query->where('status', PhoneAttemptStatus::RECEIVED->value)
                    ->orWhere(function ($query) {
                        $query->whereNotNull('sms_code')->where('sms_code', '!=', '');
                    });
            })
            ->exists();
    }

    public function canOrderReplacement(): bool
    {
        return $this->allowsReplacement()
            && $this->unexpiredAttempt() === null;
    }

    public function allowsReplacement(): bool
    {
        return $this->status === PurchaseStatus::PENDING->value
            && ! $this->hasReceivedCode()
            && ! $this->phoneAttempts()->where('status', PhoneAttemptStatus::REFUNDED->value)->exists();
    }

    public function canRequestRefund(): bool
    {
        return $this->status === PurchaseStatus::PENDING->value
            && ! $this->hasReceivedCode()
            && $this->unexpiredAttempt() === null
            && ! $this->refundRequest()->exists();
    }
}
