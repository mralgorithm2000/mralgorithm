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
        $order = Order::query()->with('orderDetails')->whereHas('orderDetails', function ($query) use ($order_id): void {
            $query->where('order_detail_key', 'user_code')->where('order_detail_value', $order_id);
        })->first();

        if ($order == '') {
            return response()->json([
                'success' => false,
                'message' => __('status.not_found'),
            ]);
        }

        $orderStatus = $this->getOrderStatus($order->supplier_order_id);
        $details = $order->orderDetails->keyBy('order_detail_key');

        Log::info('log status ordedfr', [
            'orderStatus' => $orderStatus,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('status.order_found'),
            'order' => [
                'status' => $orderStatus['status'],
                'remains' => $orderStatus['remains'],
                'link' => $details->get('link')?->order_detail_value,
                'quantity' => $details->get('quantity')?->order_detail_value,
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
