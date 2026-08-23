<?php

namespace App\Models;

use Database\Factories\OrderDetailFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDetail extends Model
{
    /** @use HasFactory<OrderDetailFactory> */
    use HasFactory;

    protected $fillable = ['order_id', 'order_detail_key', 'order_detail_name', 'order_detail_value'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
