<?php

use App\Http\Controllers\Api\OrderStatusController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;


Route::get('/payment/verify', function () {
    return view('payment.verify');
})->name('payment.verify');

Route::get('/order/status',[OrderStatusController::class,'index'])->name('order.status');
