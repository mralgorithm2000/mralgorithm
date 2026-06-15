<?php

use App\Http\Controllers\Api\OrderStatusController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/likes', function () {
    $response = Http::asForm()->post('https://my.followeran.ir/api/v2', [
        'key' => env('FOLLOWERAN_API_KEY'),
        'action' => 'services',
    ]);

    if ($response->successful()) {
        $services = $response->json();

        $data = [];
        foreach ($services as $s) {
            if ($s['brand'] == 'اینستاگرام ' && str_contains($s['category'], 'لایک')) {
                $data[] = $s;
            }
        }
        dd($data);
    } else {
        dd([
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
    }
});

Route::get('/members', function () {
    $response = Http::asForm()->post('https://my.followeran.ir/api/v2', [
        'key' => env('FOLLOWERAN_API_KEY'),
        'action' => 'services',
    ]);

    if ($response->successful()) {
        $services = $response->json();

        $data = [];
        foreach ($services as $s) {
            if ($s['brand'] == 'تلگرام' && str_contains($s['category'], 'ممبر')) {
                $data[] = $s;
            }
        }
        dd($data);
    } else {
        dd([
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
    }
});

Route::get('/tg_views', function () {
    $response = Http::asForm()->post('https://my.followeran.ir/api/v2', [
        'key' => env('FOLLOWERAN_API_KEY'),
        'action' => 'services',
    ]);

    if ($response->successful()) {
        $services = $response->json();

        $data = [];
        foreach ($services as $s) {
            if ($s['brand'] == 'تلگرام' && str_contains($s['category'], 'بازدید')) {
                $data[] = $s;
            }
        }
        dd($data);
    } else {
        dd([
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
    }
});

Route::get('/ig_followers', function () {
    $response = Http::asForm()->post('https://my.followeran.ir/api/v2', [
        'key' => env('FOLLOWERAN_API_KEY'),
        'action' => 'services',
    ]);

    if ($response->successful()) {
        $services = $response->json();

        $data = [];
        foreach ($services as $s) {
            if ($s['brand'] == 'اینستاگرام ' && str_contains($s['category'], 'فالوور خارجی اینستاگرام')) {
                $data[] = $s;
            }
        }
        dd($data);
    } else {
        dd([
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
    }
});

Route::get('/payment/verify', function () {
    return view('payment.verify');
})->name('payment.verify');

Route::get('/order/status',[OrderStatusController::class,'index'])->name('order.status');
