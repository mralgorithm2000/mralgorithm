<?php

namespace App\Http\Controllers\Api;

use App\Enums\PhoneAttemptStatus;
use App\Enums\PurchaseStatus;
use App\Enums\RefundRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\PhoneAttempt;
use App\Models\Purchase;
use App\Models\VirtualNumber;
use App\Services\DigisellerService;
use App\Services\NumberlandService;
use App\Services\SmsCodexService;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VMOrderController extends Controller
{
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'uniquecode' => ['required', 'string', 'max:255'],
        ]);

        $digiseller = new DigisellerService;
        $verification = $this->verificationData($digiseller->verifyPurchase($validated['uniquecode']));

        if (@$verification['inv'] == '') {
            return response()->json([
                'success' => false,
                'message' => __('payment.error'),
            ]);
        }

        $purchase = $this->purchaseQuery($validated['uniquecode'])->first();

        if ($purchase && ($attempt = $purchase->activeAttempt())) {
            return $this->attemptResponse($request, $purchase, $attempt);
        }

        if ($purchase) {
            return $this->attemptResponse(
                $request,
                $purchase,
                $purchase->latestAttempt(),
                $purchase->canOrderReplacement(),
            );
        }

        try {
            $job = $this->doTheJob(
                $verification['id_goods'],
                $verification['options'],
                $verification['inv'],
                $validated['uniquecode'],
                $purchase,
                $this->purchasePrices($verification),
            );
        } catch (\Exception $e) {
            Log::info('puchace ctach status', [
                'status' => 'error',
                'body' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('sms.unable_to_purchase'),
                'type' => 'purchase_error',
            ]);
        }
        // $job = $this->doTheJob($verification['id_goods'], $verification['options'], $verification['inv']);

        $this->authorizeAttemptChannel($request, $job['attempt']);

        return response()->json([
            'success' => true,
            'can_order_replacement' => false,
            'can_request_refund' => false,
            'purchase_status' => PurchaseStatus::PENDING->value,
            'refund_status' => null,
            'data' => $job['data'],
            'message' => __('payment.success'),
        ]);
    }

    public function requestRefund(Request $request)
    {
        $validated = $request->validate([
            'uniquecode' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $verification = $this->verificationData(
                (new DigisellerService)->verifyPurchase($validated['uniquecode'])
            );
            $invoiceId = $this->invoiceId($verification);

            $result = DB::transaction(function () use ($validated) {
                $purchase = $this->purchaseQuery($validated['uniquecode'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($refundRequest = $purchase->refundRequest()->first()) {
                    return [
                        'purchase' => $purchase,
                        'refund_request' => $refundRequest,
                        'created' => false,
                    ];
                }

                if (! $purchase->canRequestRefund()) {
                    return [
                        'purchase' => $purchase,
                        'refund_request' => null,
                        'created' => false,
                    ];
                }

                $refundRequest = $purchase->refundRequest()->create([
                    'status' => RefundRequestStatus::PENDING->value,
                    'reason' => $validated['reason'] ?? null,
                    'requested_at' => now(),
                ]);

                $purchase->update([
                    'status' => PurchaseStatus::REFUND_PENDING->value,
                ]);

                return [
                    'purchase' => $purchase,
                    'refund_request' => $refundRequest,
                    'created' => true,
                ];
            }, 3);
        } catch (\Exception $exception) {
            Log::error('Refund request failed', [
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('sms.unable_to_purchase'),
            ], 422);
        }

        if (! $result['refund_request']) {
            return response()->json([
                'success' => false,
                'can_request_refund' => false,
                'message' => __('sms.unable_to_purchase'),
            ], 409);
        }

        return response()->json([
            'success' => true,
            'created' => $result['created'],
            'purchase_status' => $result['purchase']->status,
            'refund_status' => $result['refund_request']->status,
            'can_request_refund' => false,
        ], $result['created'] ? 201 : 200);
    }

    public function cancelNumber(Request $request, SmsCodexService $smsCodex)
    {
        $request->merge([
            'uniqueCode' => $request->input('uniqueCode', $request->input('uniquecode')),
        ]);

        $validated = $request->validate([
            'uniqueCode' => ['required', 'string', 'max:255'],
        ]);

        $purchase = $this->purchaseQuery($validated['uniqueCode'])->first();
        $attempt = $purchase?->activeAttempt();

        if (! $purchase || ! $attempt || ! $attempt->isWaiting()) {
            return response()->json([
                'success' => false,
                'message' => __('sms.no_active_number_to_cancel'),
            ], 409);
        }

        if ($attempt->provider !== 'smscodex' || ! $attempt->provider_order_id) {
            return response()->json([
                'success' => false,
                'message' => __('sms.number_cannot_be_canceled'),
            ], 422);
        }

        try {
            $smsCodex->cancelOrder($attempt->provider_order_id);
        } catch (\Throwable $exception) {
            Log::error('Phone number cancellation failed', [
                'purchase_id' => $purchase->id,
                'phone_attempt_id' => $attempt->id,
                'provider_order_id' => $attempt->provider_order_id,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('sms.cancellation_request_failed'),
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => __('sms.cancellation_request_sent'),
        ]);
    }

    public function replacement(Request $request)
    {
        $validated = $request->validate([
            'uniquecode' => ['required', 'string', 'max:255'],
        ]);

        $purchase = $this->purchaseQuery($validated['uniquecode'])->first();

        if (! $purchase) {
            return response()->json([
                'success' => false,
                'message' => __('sms.replacement_purchase_not_found'),
            ], 404);
        }

        if (
            $purchase->status === PurchaseStatus::REFUNDED->value
            || $purchase->phoneAttempts()->where('status', PhoneAttemptStatus::REFUNDED->value)->exists()
            || $purchase->refundRequest()->where('status', RefundRequestStatus::COMPLETED->value)->exists()
        ) {
            return response()->json([
                'success' => false,
                'can_order_replacement' => false,
                'message' => __('sms.replacement_purchase_refunded'),
            ], 409);
        }

        if ($purchase->hasActiveRefundRequest() || $purchase->status === PurchaseStatus::REFUND_PENDING->value) {
            return response()->json([
                'success' => false,
                'can_order_replacement' => false,
                'message' => __('sms.replacement_refund_active'),
            ], 409);
        }

        if ($purchase->hasReceivedCode()) {
            return response()->json([
                'success' => false,
                'can_order_replacement' => false,
                'message' => __('sms.replacement_code_received'),
            ], 409);
        }

        if ($purchase->unexpiredAttempt()) {
            return response()->json([
                'success' => false,
                'can_order_replacement' => false,
                'message' => __('sms.replacement_active_attempt'),
            ], 409);
        }

        if ($purchase->status !== PurchaseStatus::PENDING->value) {
            return response()->json([
                'success' => false,
                'can_order_replacement' => false,
                'message' => __('sms.replacement_not_available'),
            ], 409);
        }

        try {
            $attempt = DB::transaction(function () use ($purchase) {
                $lockedPurchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);

                if (! $lockedPurchase->canOrderReplacement()) {
                    throw new \RuntimeException(__('sms.replacement_state_changed'), 409);
                }

                $service = $lockedPurchase->purchasable;

                if (! $service instanceof VirtualNumber) {
                    throw new \RuntimeException(__('sms.replacement_not_available'), 409);
                }

                $serviceClass = $this->getSourceService($service->source);

                if (! $serviceClass) {
                    throw new \RuntimeException(__('sms.replacement_not_available'), 409);
                }

                return app($serviceClass)->getNumber($service, $lockedPurchase);
            }, 3);
        } catch (\Throwable $exception) {
            Log::error('Replacement phone number order failed', [
                'purchase_id' => $purchase->id,
                'exception' => $exception->getMessage(),
            ]);

            $isConflict = $exception->getCode() === 409;

            return response()->json([
                'success' => false,
                'can_order_replacement' => $isConflict ? false : $purchase->fresh()->canOrderReplacement(),
                'message' => $isConflict ? $exception->getMessage() : __('sms.replacement_order_failed'),
            ], $isConflict ? 409 : 502);
        }

        $purchase->refresh();
        $this->authorizeAttemptChannel($request, $attempt);
        $serviceDetails = $this->getServiceDetails($purchase->purchasable?->type);
        $statusDetails = $this->getStatusDetails($attempt->status);

        return response()->json([
            'success' => true,
            'can_order_replacement' => false,
            'can_request_refund' => false,
            'purchase_status' => $purchase->status,
            'refund_status' => null,
            'data' => [
                'number' => $attempt->phone_number,
                'country_code' => $attempt->country_code,
                'expires_at' => $this->dateToMinutes($attempt->expires_at),
                'serviceName' => $serviceDetails['name'],
                'serviceIcon' => $serviceDetails['icon'],
                'status' => $statusDetails['value'],
                'statusLabel' => $statusDetails['label'],
                'order_id' => $attempt->id,
                'sms_code' => '',
            ],
            'message' => __('sms.replacement_ordered'),
        ], 201);
    }

    private function doTheJob($service_id, $options, $invoice_id, string $uniqueCode, ?Purchase $purchase, array $prices)
    {
        $optionsArr = [];

        foreach ($options as $option) {
            $optionsArr[$option['id']] = $option['variant_id'] ?? $option['value'];
        }

        $serviceTypeId = Option::where('plati_id', $service_id)->where('type', 'country')->value('option_id');

        $plati_id = $optionsArr[$serviceTypeId];

        $service = VirtualNumber::where('plati_id', $plati_id)->first();

        if (! $service) {
            throw new \RuntimeException('The purchased virtual-number service was not found.');
        }

        $serviceDetails = $this->getServiceDetails($service->type);

        if (! $purchase) {
            try {
                $purchase = DB::transaction(fn () => $service->purchases()->create([
                    'marketplace' => 'plati',
                    'external_order_id' => (string) $uniqueCode,
                    ...$prices,
                    'status' => PurchaseStatus::PENDING->value,
                ]), 3);
            } catch (UniqueConstraintViolationException) {
                $purchase = Purchase::query()
                    ->where('marketplace', 'plati')
                    ->where('external_order_id', (string) $uniqueCode)
                    ->firstOrFail();
            }
        }

        $serviceClass = $this->getSourceService($service->source);
        $serviceInstance = new $serviceClass;
        $attempt = DB::transaction(
            fn () => $serviceInstance->getNumber($service, $purchase, $prices),
            3,
        );

        $statusDetails = $this->getStatusDetails($attempt->status);

        return [
            'attempt' => $attempt,
            'data' => [
                'number' => $attempt->phone_number,
                'country_code' => $attempt->country_code,
                'expires_at' => $this->dateToMinutes($attempt->expires_at),
                'serviceName' => $serviceDetails['name'],
                'serviceIcon' => $serviceDetails['icon'],
                'status' => $statusDetails['value'],
                'statusLabel' => $statusDetails['label'],
                'order_id' => $attempt->id,
                'sms_code' => '',
            ],
        ];
    }

    private function authorizeAttemptChannel(Request $request, PhoneAttempt $attempt): void
    {
        $request->session()->put("phone_attempt_ids.{$attempt->id}", true);
    }

    private function attemptResponse(
        Request $request,
        Purchase $purchase,
        ?PhoneAttempt $attempt,
        bool $canOrderReplacement = false,
    ) {
        if ($attempt) {
            $this->authorizeAttemptChannel($request, $attempt);
        }

        $service = $purchase->purchasable;
        $serviceDetails = $this->getServiceDetails($service instanceof VirtualNumber ? $service->type : null);
        $statusDetails = $this->getStatusDetails($attempt?->status);

        return response()->json([
            'success' => true,
            'can_order_replacement' => $canOrderReplacement,
            'can_request_refund' => $purchase->canRequestRefund(),
            'purchase_status' => $purchase->status,
            'refund_status' => $purchase->refundRequest()->value('status'),
            'data' => [
                'number' => $attempt?->phone_number,
                'country_code' => $attempt?->country_code,
                'expires_at' => $attempt ? $this->dateToMinutes($attempt->expires_at) : 0,
                'serviceName' => $serviceDetails['name'],
                'serviceIcon' => $serviceDetails['icon'],
                'status' => $statusDetails['value'],
                'statusLabel' => $statusDetails['label'],
                'order_id' => $attempt?->id,
                'sms_code' => $attempt?->sms_code,
            ],
            'message' => __('payment.success'),
        ]);
    }

    private function getStatusDetails(?string $status): array
    {
        $orderStatus = PhoneAttemptStatus::tryFrom((string) $status)
            ?? PhoneAttemptStatus::WAITING;

        return [
            'value' => $orderStatus->value,
            'label' => $orderStatus->label(),
        ];
    }

    private function getServiceDetails(?string $type): array
    {
        $serviceMap = [
            'telegram' => [
                'name' => 'Telegram',
                'icon' => asset('storage/icons/telegram.png'),
            ],
            'instagram' => [
                'name' => 'Instagram',
                'icon' => asset('storage/icons/instagram.png'),
            ],
            'openai' => [
                'name' => 'OpenAI',
                'icon' => asset('storage/icons/openai.png'),
            ],
            'whatsapp' => [
                'name' => 'WhatsApp',
                'icon' => asset('storage/icons/whatsapp.png'),
            ],
            'google' => [
                'name' => 'Google',
                'icon' => asset('storage/icons/google.png'),
            ],
        ];

        $serviceType = Str::lower(trim((string) $type));

        return $serviceMap[$serviceType] ?? [
            'name' => Str::headline($serviceType),
            'icon' => null,
        ];
    }

    private function getSourceService($serviceType)
    {
        switch ($serviceType) {
            case 'smscodex':
                return SmsCodexService::class;
            case 'numberland':
                return NumberlandService::class;
        }
    }

    private function verificationData(array $verification): array
    {
        return isset($verification['response']) && is_array($verification['response'])
            ? array_replace($verification, $verification['response'])
            : $verification;
    }

    private function invoiceId(array $verification): string
    {
        $invoiceId = (string) ($verification['inv'] ?? '');

        abort_if($invoiceId === '', 422, __('payment.error'));

        return $invoiceId;
    }

    private function purchaseQuery(int|string $uniquecode)
    {
        return Purchase::query()
            ->where('marketplace', 'plati')
            ->where('external_order_id', (string) $uniquecode);
    }

    private function purchasePrices(array $verification): array
    {
        $sold = max(0, (float) ($verification['amount_usd'] ?? 0));
        // DigiSeller documents profit as the seller's net proceeds after its fee.
        $profit = max(0, (float) ($verification['profit'] ?? $sold));

        return [
            'sold_price' => $sold,
            'marketplace_fee' => max(0, $sold - $profit),
            'cost_price' => 0,
            'refunded_amount' => 0,
        ];
    }

    private function dateToMinutes($date)
    {
        $now = Carbon::now();
        $expires = Carbon::parse($date);

        return max(0, $now->diffInSeconds($expires, false));
    }
}
