<?php

namespace Database\Seeders;

use App\Models\Type;
use Illuminate\Database\Seeder;

class TypeSeeder extends Seeder
{
    /** @var array<string, list<array{type_key: string, type_name: string, type: string, options: list<string>|null}>> */
    private const TYPES = [
        'Virtual Number' => [
            [
                'type_key' => 'virtual_number_country',
                'type_name' => 'Country',
                'type' => 'dropdown',
                'options' => ['United States', 'United Kingdom', 'Germany', 'Russia'],
            ],
            [
                'type_key' => 'virtual_number_service',
                'type_name' => 'Service',
                'type' => 'dropdown',
                'options' => ['Telegram', 'WhatsApp', 'Google', 'Instagram'],
            ],
            [
                'type_key' => 'virtual_number_quantity',
                'type_name' => 'Quantity',
                'type' => 'text',
                'options' => null,
            ],
        ],
        'SMM' => [
            [
                'type_key' => 'smm_platform',
                'type_name' => 'Platform',
                'type' => 'dropdown',
                'options' => ['Instagram', 'Telegram', 'YouTube', 'TikTok'],
            ],
            [
                'type_key' => 'smm_service',
                'type_name' => 'Service',
                'type' => 'multiple_choice',
                'options' => ['Followers', 'Likes', 'Views', 'Comments'],
            ],
            [
                'type_key' => 'smm_quantity',
                'type_name' => 'Quantity',
                'type' => 'text',
                'options' => null,
            ],
        ],
        'Gift Cards' => [
            [
                'type_key' => 'gift_card_brand',
                'type_name' => 'Brand',
                'type' => 'dropdown',
                'options' => ['Amazon', 'Apple', 'Google Play', 'Steam'],
            ],
            [
                'type_key' => 'gift_card_region',
                'type_name' => 'Region',
                'type' => 'dropdown',
                'options' => ['United States', 'Europe', 'United Kingdom', 'Global'],
            ],
            [
                'type_key' => 'gift_card_value',
                'type_name' => 'Card Value',
                'type' => 'text',
                'options' => null,
            ],
        ],
        'Vouchers' => [
            [
                'type_key' => 'voucher_brand',
                'type_name' => 'Brand',
                'type' => 'text',
                'options' => null,
            ],
            [
                'type_key' => 'voucher_region',
                'type_name' => 'Region',
                'type' => 'dropdown',
                'options' => ['United States', 'Europe', 'United Kingdom', 'Global'],
            ],
            [
                'type_key' => 'voucher_value',
                'type_name' => 'Voucher Value',
                'type' => 'text',
                'options' => null,
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::TYPES as $title => $items) {
            $type = Type::firstOrCreate(['title' => $title]);

            foreach ($items as $item) {
                $type->items()->updateOrCreate(
                    ['type_key' => $item['type_key']],
                    $item,
                );
            }
        }
    }
}
