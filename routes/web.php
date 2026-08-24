<?php

use App\Http\Controllers\Api\OrderStatusController;
use App\Models\GoodsMarketplaceMapping;
use App\Models\MarketplaceOptionMapping;
use App\Models\MarketplaceParameterMapping;
use App\Models\Parameter;
use App\Models\ParameterOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/payment/verify', function () {
    return view('payment.verify');
})->name('payment.verify');

Route::get('/order/status', [OrderStatusController::class, 'index'])->name('order.status');

Route::get('/order/vn', function () {
    return view('order.vn');
})->name('order.vn');

// Route::get('/hi', function () {
//     $maGoods = [
//         4 => 'instagram_like',
//         1 => 'telegram_member',
//         2 => 'telegram_views',
//         5 => 'instagram_follower',
//         6 => 'instagram_view',
//         7 => 'instagram_view_story',
//         3 => 'telegram_reactions',
//         8 => 'youtube_watchtime',
//         9 => 'youtube_like',
//         10 => 'tiktok_follower',
//         11 => 'tiktok_likes',
//     ];

//     $goods = GoodsMarketplaceMapping::get();

//     foreach ($goods as $good) {
//         $maGood = $maGoods[$good->id] ?? null;
//         $options = DB::table('options')->where('plati_id', $good->marketplace_product_id)->get();
//         foreach ($options as $option) {
//             $parameter = Parameter::create([
//                 'title' => $option->title,
//                 'parameter_key' => $option->type,
//                 'type' => ($option->type == 'link') ? 'text' : 'dropdown',
//                 'goods_id' => $good->id,
//                 'is_main' => ($option->type == 'link') ? false : true,
//             ]);

//             MarketplaceParameterMapping::create([
//                 'marketplace' => 'plati',
//                 'marketplace_parameter_id' => $option->option_id,
//                 'parameter_id' => $parameter->id,
//             ]);

//             if ($option->type != 'link') {
//                 $sergices = DB::table('sm_services')->where('type', $maGood)->get();
//                 foreach ($sergices as $service) {
//                     $parameteropt = ParameterOption::create([
//                         'parameter_id' => $parameter->id,
//                         'option_name' => explode('-', $service->name)[0],
//                         'option_value' => $maGood,
//                         'operator' => '%',
//                         'additional_price' => 0,
//                         'original_price' => 0,
//                         'selling_price' => 0,
//                         'supplier_id' => 4,
//                         'supplier_product_id' => $service->api_id,
//                     ]);

//                     MarketplaceOptionMapping::create([
//                         'marketplace' => 'plati',
//                         'marketplace_option_id' => $service->plati_id,
//                         'parameter_option_id' => $parameteropt->id,
//                     ]);
//                 }

//             }
//         }
//     }

// })->name('order.vn');
