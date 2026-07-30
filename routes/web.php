<?php

use App\Http\Controllers\Api\OrderStatusController;
use App\Services\DigisellerService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::get('/payment/verify', function () {
    return view('payment.verify');
})->name('payment.verify');

Route::get('/order/status',[OrderStatusController::class,'index'])->name('order.status');


Route::get('/order/vn', function () {
    return view('order.vn');
})->name('order.vn');




Route::get('/test1234', function () {
    $digiseller = new DigisellerService();
    $digiseller->markAsDelivered("F987E0311D8D4C1F");
})->name('order.vn');
