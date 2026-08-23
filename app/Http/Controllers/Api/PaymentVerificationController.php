<?php

namespace App\Http\Controllers\Api;

use App;
use App\Http\Controllers\Controller;
use App\Models\Good;
use App\Models\Option;
use App\Models\Order;
use App\Models\OrderTempInfo;
use App\Models\Parameter;
use App\Models\ParameterOption;
use App\Models\Purchase;
use App\Models\SmService;
use App\Services\DigisellerService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentVerificationController extends Controller

{
    public function plati_verify(Request $request)
    {
        $validated = $request->validate([
            'uniquecode' => ['required', 'string', 'max:255'],
            'lang' => 'required'
        ]);

        $uniqueCode = $validated['uniquecode'];
        $lang = $validated['lang'];
        App::setLocale($lang);

        $existing = Purchase::query()
            ->where('marketplace', 'plati')
            ->where('marketplace_order_id', $uniqueCode)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'order_id' => $existing->marketplace_order_id,
                'message' => __('payment.payment_already_verified'),
            ]);
        }

        $digiseller = new DigisellerService;
        $verification = $this->verificationData($digiseller->verifyPurchase($uniqueCode));
        $invoiceId = (string) ($verification['inv'] ?? '');
        $options = $verification['options'] ?? [];

        if ($invoiceId === '') {
            return response()->json([
                'success' => false,
                'message' => __('payment.error'),
            ], 422);
        }

        $goods = Good::join('goods_marketplace_mappings', 'goods.id', '=', 'goods_marketplace_mappings.good_id')
            ->where('goods_marketplace_mappings.marketplace', 'plati')
            ->where('goods_marketplace_mappings.marketplace_product_id', $verification['id_goods'])
            ->select(['goods.id'])
            ->first();

        $parameter = Parameter::join('marketplace_parameter_mappings', 'parameters.id', '=', 'marketplace_parameter_mappings.parameter_id')
            ->where('marketplace_parameter_mappings.marketplace', 'plati')
            ->where('parameters.goods_id', $goods['id'])
            ->where('parameters.is_main', true)
            ->select(['parameters.id', 'marketplace_parameter_mappings.marketplace_parameter_id'])
            ->first();

        $optionsArr = [];

        foreach ($options as $option) {
            $optionsArr[$option['id']] = $option['variant_id'] ?? $option['value'];
        }

        Log::info('Plati verification data', [
            'options' => $options,
            'uniqueCode' => $uniqueCode,
            'verification' => $verification,
            'goods' => $goods,
            'parameter' => $parameter,
            'optionsArr' => $optionsArr,
        ]);

        $optionId = $optionsArr[$parameter['marketplace_parameter_id']] ?? null;

        $option = ParameterOption::join('marketplace_option_mappings', 'parameter_options.id', '=', 'marketplace_option_mappings.parameter_option_id')
            ->where('parameter_options.parameter_id', $parameter['id'])
            ->where('marketplace_option_mappings.marketplace_option_id', $optionId)
            ->first();

        Purchase::create([
            'marketplace' => 'plati',
            'marketplace_order_id' => $uniqueCode,
            'goods_id' => $goods->id ?? null,
            'sold_price' => $verification['amount_usd'],
        ]);

        $normalizedResponse = $this->normilizewResponse($verification);

        OrderTempInfo::create([
            'purchase_id' => Purchase::where('marketplace_order_id', $uniqueCode)->first()->id,
            'order_detail' => json_encode($normalizedResponse),
            'option_id' => $option->id ?? null,
        ]);

        return response()->json([
            'success' => true,
            'order_id' => $verification['inv'],
            'message' => __('payment.success'),
        ]);
    }


    private function verificationData(array $verification): array
    {
        return isset($verification['response']) && is_array($verification['response'])
            ? array_replace($verification, $verification['response'])
            : $verification;
    }

    private function normilizewResponse($response){
        $parameters = [];

        if(isset($response['options']) && is_array($response['options'])){
            foreach ($response['options'] as $option) {
                $parameter = Parameter::join('marketplace_parameter_mappings', 'parameters.id', '=', 'marketplace_parameter_mappings.parameter_id')
                    ->where('marketplace_parameter_mappings.marketplace', 'plati')
                    ->where('marketplace_parameter_mappings.marketplace_parameter_id', $option['id'])
                    ->select(['parameters.id', 'parameters.parameter_key', 'marketplace_parameter_mappings.marketplace_parameter_id'])
                    ->first();

                $parameter_value = '';

                if($option['variant_id']){
                    $myDBOption = ParameterOption::join('marketplace_option_mappings', 'parameter_options.id', '=', 'marketplace_option_mappings.parameter_option_id')
                        ->where('parameter_options.parameter_id', $parameter['id'])
                        ->where('marketplace_option_mappings.marketplace_option_id', $option['variant_id'])
                        ->select(['parameter_options.id', 'marketplace_option_mappings.marketplace_option_id', 'parameter_options.option_value'])
                        ->first();

                    $parameter_value = $myDBOption->option_value ?? '';
                }else{
                    $parameter_value = $option['value'] ?? '';
                }

                $parameters[$parameter['parameter_key']] = $parameter_value;
            }
        }

        $data = [
            'amount' => $response['amount'] ?? 0,
            'amount_usd' => $response['amount_usd'] ?? 0,
            'profit' => $response['profit'] ?? 0,
            'email' => $response['email'] ?? '',
            'quantity' => $response['cnt_goods'] ?? 0,
            'options' => $parameters,
        ];

        return $data;

    }
}
