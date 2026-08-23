<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderTempInfo extends Model
{
    protected $table = 'order_temp_info';

    protected $fillable = [
        'purchase_id',
        'order_detail',
        'option_id',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }
}
