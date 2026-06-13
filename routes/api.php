<?php

use App\Http\Controllers\Api\OrderStatusController;
use App\Http\Controllers\Api\PaymentVerificationController;
use App\Http\Controllers\Followeran\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('do',[ApiController::class,'send']);

Route::post('verify', [PaymentVerificationController::class, 'verify'])
    ->middleware('throttle:6,1');

Route::post('order-status', [OrderStatusController::class, 'check'])
    ->middleware('throttle:6,1');
