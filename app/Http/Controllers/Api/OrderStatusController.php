<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderStatusController extends Controller
{
    public function index()
    {
        return view('order.status');
    }

    public function check(Request $request)
    {
        $order_id = $request->post('order_id');
        $order = Order::where('user_code', $order_id)->first();

        if ($order == '') {
            return response()->json([
                'success' => false,
                'message' => __('status.not_found'),
            ]);
        }

        $orderStatus = $this->getOrderStatus($order->order_id);

        Log::info('log status ordedfr',[
            'orderStatus' => $orderStatus
        ]);

        return response()->json([
            'success' => true,
            'message' => __('status.order_found'),
            'order' => [
                'status' => $orderStatus['status'],
                'remains' => $orderStatus['remains'],
                'link' => $order['link'],
                'quantity' => $order['quantity'],
            ],
        ]);
    }

    public function getOrderStatus(string $orderId): array
    {
        $response = Http::asForm()->post('https://my.followeran.ir/api/v2', [
            'key' => env('FOLLOWERAN_API_KEY'),
            'action' => 'status',
            'order' => $orderId,
        ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to retrieve order status.');
        }

        return $response->json();
    }
}
