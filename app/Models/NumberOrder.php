<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NumberOrder extends Model
{

    protected $fillable = [
        'virtual_number_id',
        'plati_order_id',
        'phone_number',
        'country_code',
        'sms_code',
        'status',
        'expires_at',
    ];


    protected $casts = [
        'expires_at' => 'datetime',
    ];


    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */


    public function virtualNumber()
    {
        return $this->belongsTo(
            VirtualNumber::class
        );
    }

    public function service()
    {
        return $this->belongsTo(VirtualNumber::class, 'virtual_number_id', 'id');
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */


    public function isWaiting()
    {
        return $this->status === 'waiting';
    }


    public function isReceived()
    {
        return $this->status === 'received';
    }


    public function isExpired()
    {
        return $this->status === 'expired';
    }


    public function receiveCode($code)
    {
        $this->update([
            'sms_code' => $code,
            'status' => 'received',
        ]);
    }
}
