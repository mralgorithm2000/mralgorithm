<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = "orders";
    protected $fillable = [
        'order_id',
        'status',
        'link',
        'api_id',
        'service_id',
        'quantity',
        'error',
        'user_code'
    ];
}
