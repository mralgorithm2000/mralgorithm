<?php

namespace App\Services\Suppliers;

use App\Models\Order;
use App\Models\OrderTempInfo;
use App\Models\ParameterOption;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FolloweranService
{
    public function checkOrderStatus($purchase)
    {
        $order = Order::where('purchase_id', $purchase->id)->first();

        if ($order) {
            return $this->checkStatus($order);
        } else {
            return $this->createNew($purchase);
        }
    }

    private function checkStatus($order)
    {
        $order_status = $this->getOrderStatus($order->supplier_order_id);

        $quantity = $order->orderDetails()->where('order_detail_key', 'quantity')->value('order_detail_value');
        $link = $order->orderDetails()->where('order_detail_key', 'link')->value('order_detail_value');
        $status = ($order_status['status'] == 'Completed') ? 'completed' : 'processing';
        $message = ($order_status['status'] == 'Completed') ? __('order.completed') : __('order.started');

        return [
            'success' => true,
            'tracking_code' => $order['tracking_code'] ?? null,
            'message' => $message,
            'sub_message' => null,
            'status' => $status,
            'details' => [
                [
                    'title' => __('order.remaining'),
                    'value' => $order_status['remains'] ?? $quantity,
                ],
                [
                    'title' => __('order.link'),
                    'value' => $link,
                ],
            ],
        ];
    }

    private function createNew($purcahse)
    {
        $order_temp_info = OrderTempInfo::where('purchase_id', $purcahse->id)->first();
        $option = ParameterOption::where('id', $order_temp_info->option_id)->first();
        $orderDetail = json_decode($order_temp_info->order_detail, 1);

        $options = $orderDetail['options'] ?? [];

        try {
            $api_response = $this->sendRequest($options['link'], $option->supplier_product_id, $orderDetail['quantity']);
        } catch (\Exception $e) {
            Log::error('Error creating order with Followeran API: '.$e->getMessage());

            return [
                'success' => false,
                'message' => __('order.creation_failed'),
                'details' => [],
            ];
        }
        $orderDetail['supplier_product_id'] = $option->supplier_product_id;
        $order = $this->createOrder($purcahse, $api_response, $orderDetail);
        $this->updatePurchase($purcahse, $api_response);

        $success = $order['status'] != 'failed' ? true : false;

        return [
            'success' => $success,
            'tracking_code' => $order['tracking_code'] ?? null,
            'message' => __('order.started'),
            'sub_message' => null,
            'status' => 'processing',
            'details' => [
                [
                    'title' => __('order.remaining'),
                    'value' => $orderDetail['quantity'] ?? 0,
                ],
                [
                    'title' => __('order.link'),
                    'value' => $options['link'],
                ],
            ],
        ];
    }

    private function sendRequest($link, $serviceId, $quantity)
    {
        $response = Http::asForm()
            ->connectTimeout(10)
            ->timeout(30)
            ->retry(3, 1000)
            ->post('https://my.followeran.ir/api/v2', [
                'key' => env('FOLLOWERAN_API_KEY'),
                'action' => 'add',
                'service' => $serviceId,
                'link' => $link,
                'quantity' => $quantity,
                'is_test' => 0,
            ]);

        Log::info('api response', [
            'response' => $response->json(),
        ]);

        if (! $response->successful() || ! isset($response->json()['status']) || $response->json()['status'] == 'failed') {
            throw new \Exception('Failed to retrieve order API');
        }

        $result = $response->json();

        return $result;
    }

    private function createOrder($purchase, $api_response, $order_detail)
    {
        $actualCost = $api_response['charge'] ?? $api_response['cost'] ?? $api_response['price'] ?? null;

        $status = ($api_response['status'] == 'success') ? 'processing' : 'failed';
        $order = Order::create([
            'purchase_id' => $purchase->id,
            'supplier_order_id' => $api_response['order'] ?? null,
            'status' => $status,
            'sold_price' => $purchase->sold_price,
            'cost_price' => (float) $actualCost ?? 0,
            'tracking_code' => 'MR-'.Str::random(10),
        ]);

        $options = $order_detail['options'] ?? [];

        $order->orderDetails()->createMany([
            ['order_detail_key' => 'link', 'order_detail_name' => 'Link', 'order_detail_value' => $options['link']],
            ['order_detail_key' => 'quantity', 'order_detail_name' => 'Quantity', 'order_detail_value' => $order_detail['quantity']],
            ['order_detail_key' => 'supplier_product_id', 'order_detail_name' => 'Supplier Product ID', 'order_detail_value' => $order_detail['supplier_product_id']],
        ]);

        return $order;
    }

    private function updatePurchase($purchase, $api_response)
    {
        $actualCost = $api_response['charge'] ?? $api_response['cost'] ?? $api_response['price'] ?? null;
        if (is_numeric($actualCost) && (float) $actualCost > 0) {
            $purchase->increment('cost_price', (float) $actualCost);
        }
    }

    public function getOrderStatus(string $orderId): array
    {
        $response = Http::asForm()
            ->connectTimeout(10)
            ->timeout(30)
            ->retry(3, 1000)
            ->post('https://my.followeran.ir/api/v2', [
                'key' => env('FOLLOWERAN_API_KEY'),
                'action' => 'status',
                'order' => $orderId,
            ]);

        Log::info('log status order', [
            'orderId' => $orderId,
            'response' => $response->json(),
        ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to retrieve order API');
        }

        return $response->json();
    }
}
