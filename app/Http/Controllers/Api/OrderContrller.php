<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderTempInfo;
use App\Models\ParameterOption;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Log;

class OrderContrller extends Controller
{
    public function check(Request $request)
    {
        $validated = $request->validate([
            'uniquecode' => ['required', 'string', 'max:255'],
            'lang' => 'required',
        ]);

        $uniqueCode = $validated['uniquecode'];
        $lang = $validated['lang'];
        app()->setLocale($lang);

        $purchase = Purchase::query()
            ->where('marketplace', 'plati')
            ->where('marketplace_order_id', $uniqueCode)
            ->first();

        if (! $purchase) {
            return response()->json([
                'success' => false,
                'message' => __('order.not_found'),
            ], 404);
        }

        $order_temp_info = OrderTempInfo::where('purchase_id', $purchase->id)->first();
        $option = ParameterOption::where('id', $order_temp_info->option_id)->first();

        $serviceClass = $this->supplierServiceMapping($option->supplier_id);

        if (! $serviceClass) {
            throw new \Exception(
                "Unsupported supplier ID: {$option->supplier_id}"
            );
        }

        $serviceInstance = new $serviceClass;
        $order = $serviceInstance->checkOrderStatus($purchase);

        $goodPLatiId = $purchase->good->marketplaceMappings->where('marketplace','plati')->first()->marketplace_product_id ?? null;

        return response()->json([
            'success' => $order['success'],
            'tracking_code' => $order['tracking_code'] ?? null,
            'message' => $order['message'],
            'submessage' => $order['sub_message'] ?? null,
            'details' => $order['details'] ?? [],
            'status' => $order['status'] ?? null,
            'order_again_url' => $goodPLatiId ? "https://plati.market/itm/{$goodPLatiId}" : null
        ]);
    }

    private function supplierServiceMapping($supplierId)
    {
        return match ($supplierId) {
            1 => \App\Services\Suppliers\SmsCodexService::class,
            2 => \App\Services\Suppliers\FazercardsService::class,
            3 => \App\Services\Suppliers\NumberlandService::class,
            4 => \App\Services\Suppliers\FolloweranService::class,
            default => null,
        };
    }
}
