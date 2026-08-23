<?php

namespace App\Models;

use App\Enums\PhoneAttemptStatus;
use App\Enums\PurchaseStatus;
use App\Enums\RefundRequestStatus;
use Database\Factories\PurchaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Purchase extends Model
{
    /** @use HasFactory<PurchaseFactory> */
    use HasFactory;

    protected $fillable = [
        'marketplace',
        'marketplace_order_id',
        'goods_id',
        'sold_price',
        'cost_price',
        'marketplace_fee',
        'refunded_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sold_price' => 'decimal:6',
            'cost_price' => 'decimal:6',
            'marketplace_fee' => 'decimal:6',
            'refunded_amount' => 'decimal:6',
        ];
    }

    public function good(): BelongsTo
    {
        return $this->belongsTo(Good::class, 'goods_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
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
            && $this->unexpiredAttempt() === null
            && ! $this->hasActiveRefundRequest();
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

    public function hasActiveRefundRequest(): bool
    {
        return $this->refundRequest()
            ->whereIn('status', [
                RefundRequestStatus::PENDING->value,
                RefundRequestStatus::APPROVED->value,
            ])
            ->exists();
    }
}
