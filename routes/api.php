<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\GoodController;
use App\Http\Controllers\Admin\GoodMarketplaceController;
use App\Http\Controllers\Admin\GoodParameterController;
use App\Http\Controllers\Admin\GoodPlatiParameterController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ParameterOptionController;
use App\Http\Controllers\Admin\ParameterOptionPlatiController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\RefundRequestController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\TypeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\OrderContrller;
use App\Http\Controllers\Api\OrderStatusController;
use App\Http\Controllers\Api\PaymentVerificationController;
use App\Http\Controllers\Api\SmsWebhookController;
use App\Http\Controllers\Api\VMOrderController;
use App\Http\Controllers\Followeran\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:6,1');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');

Route::put('/profile', [ProfileController::class, 'update'])
    ->middleware('auth:sanctum');

Route::apiResource('roles', RoleController::class)
    ->middleware('auth:sanctum');

Route::apiResource('users', UserController::class)
    ->middleware('auth:sanctum');

Route::get('types/all', [TypeController::class, 'all'])
    ->middleware('auth:sanctum');

Route::get('types/{type}/items', [TypeController::class, 'items'])
    ->middleware('auth:sanctum');

Route::apiResource('types', TypeController::class)
    ->middleware('auth:sanctum');

Route::apiResource('goods', GoodController::class)
    ->middleware('auth:sanctum');
Route::post('goods/{good}/digiseller/fixed', [GoodController::class, 'publishFixedPriceToDigiseller'])
    ->middleware('auth:sanctum');
Route::post('goods/{good}/digiseller/variable', [GoodController::class, 'publishVariablePriceToDigiseller'])
    ->middleware('auth:sanctum');
Route::get('goods/{good}/parameters', [GoodParameterController::class, 'index'])
    ->middleware('auth:sanctum');
Route::post('goods/{good}/parameters', [GoodParameterController::class, 'store'])
    ->middleware('auth:sanctum');
Route::put('goods/{good}/parameters/{parameter}', [GoodParameterController::class, 'update'])
    ->middleware('auth:sanctum');
Route::post('goods/{good}/parameters/plati', [GoodPlatiParameterController::class, 'store'])
    ->middleware('auth:sanctum');
Route::get('goods/{good}/marketplaces', [GoodMarketplaceController::class, 'index'])
    ->middleware('auth:sanctum');
Route::post('parameters/{parameter}/options', [ParameterOptionController::class, 'store'])
    ->middleware('auth:sanctum');
Route::get('parameters/{parameter}/options', [ParameterOptionController::class, 'index'])
    ->middleware('auth:sanctum');
Route::put('parameters/{parameter}/options/{option}', [ParameterOptionController::class, 'update'])
    ->middleware('auth:sanctum');
Route::delete('parameters/{parameter}/options/{option}', [ParameterOptionController::class, 'destroy'])
    ->middleware('auth:sanctum');
Route::post('parameters/{parameter}/options/plati', [ParameterOptionPlatiController::class, 'store'])
    ->middleware('auth:sanctum');

Route::get('purchases', [PurchaseController::class, 'index'])
    ->middleware('auth:sanctum');
Route::get('purchases/{purchase}', [PurchaseController::class, 'show'])
    ->middleware('auth:sanctum');

Route::get('refund-requests', [RefundRequestController::class, 'index'])
    ->middleware('auth:sanctum');
Route::put('refund-requests/{refundRequest}/status', [RefundRequestController::class, 'updateStatus'])
    ->middleware('auth:sanctum');
Route::get('refund-requests/{refundRequest}', [RefundRequestController::class, 'show'])
    ->middleware('auth:sanctum');

Route::get('orders', [OrderController::class, 'index'])
    ->middleware('auth:sanctum');
Route::get('orders/{order}', [OrderController::class, 'show'])
    ->middleware('auth:sanctum');

Route::get('suppliers/all', [SupplierController::class, 'all'])
    ->middleware('auth:sanctum');

Route::apiResource('suppliers', SupplierController::class)
    ->middleware('auth:sanctum');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('do', [ApiController::class, 'send']);

Route::post('verify', [PaymentVerificationController::class, 'verify'])
    ->middleware('throttle:6,1');

Route::post('order-status', [OrderStatusController::class, 'check'])
    ->middleware('throttle:6,1');

Route::post('vm/verify', [VMOrderController::class, 'verify'])
    ->middleware(['web', 'throttle:6,1']);

Route::post('vm/refund-request', [VMOrderController::class, 'requestRefund'])
    ->middleware(['web', 'throttle:6,1']);

Route::post('vm/replacement', [VMOrderController::class, 'replacement'])
    ->middleware(['web', 'throttle:6,1']);

Route::post('vm/cancel-number', [VMOrderController::class, 'cancelNumber'])
    ->middleware(['web', 'throttle:6,1']);

Route::post('sms/webhook/smscodex', [SmsWebhookController::class, 'smscodex'])
    ->middleware('throttle:60,1');



//new version

Route::post('payment/verify/plati', [PaymentVerificationController::class, 'plati_verify'])
    ->middleware('throttle:6,1');


Route::post('order/check/plati', [OrderContrller::class, 'check'])
    ->middleware('throttle:6,1');
