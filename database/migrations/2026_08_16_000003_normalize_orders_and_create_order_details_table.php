<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('order_detail_key');
            $table->string('order_detail_name');
            $table->text('order_detail_value');
            $table->timestamps();
            $table->index(['order_id', 'order_detail_key']);
        });

        $legacyColumns = [
            'link' => 'Link', 'api_id' => 'API ID', 'service_id' => 'Service ID',
            'quantity' => 'Quantity', 'error' => 'Error', 'user_code' => 'User Code',
        ];

        DB::table('orders')->orderBy('id')->chunkById(100, function ($orders) use ($legacyColumns): void {
            $details = [];
            foreach ($orders as $order) {
                foreach ($legacyColumns as $key => $name) {
                    if ($order->{$key} === null) {
                        continue;
                    }
                    $details[] = [
                        'order_id' => $order->id,
                        'order_detail_key' => $key,
                        'order_detail_name' => $name,
                        'order_detail_value' => (string) $order->{$key},
                        'created_at' => $order->created_at,
                        'updated_at' => $order->updated_at,
                    ];
                }
            }
            if ($details !== []) {
                DB::table('order_details')->insert($details);
            }
        });

        Schema::table('orders', fn (Blueprint $table) => $table->renameColumn('order_id', 'supplier_order_id'));
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('sold_price', 18, 6)->default(0)->after('status');
            $table->decimal('cost_price', 18, 6)->default(0)->after('sold_price');
            $table->index('supplier_order_id');
            $table->index('status');
            $table->dropColumn(['link', 'api_id', 'service_id', 'quantity', 'error', 'user_code']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('link')->nullable();
            $table->string('api_id')->nullable();
            $table->string('service_id')->nullable();
            $table->string('quantity')->nullable();
            $table->text('error')->nullable();
            $table->string('user_code')->nullable();
        });

        DB::table('order_details')->whereIn('order_detail_key', [
            'link', 'api_id', 'service_id', 'quantity', 'error', 'user_code',
        ])->orderBy('id')->each(function (object $detail): void {
            DB::table('orders')->where('id', $detail->order_id)->update([
                $detail->order_detail_key => $detail->order_detail_value,
            ]);
        });

        Schema::dropIfExists('order_details');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['supplier_order_id']);
            $table->dropIndex(['status']);
            $table->dropColumn(['sold_price', 'cost_price']);
        });
        Schema::table('orders', fn (Blueprint $table) => $table->renameColumn('supplier_order_id', 'order_id'));
    }
};
