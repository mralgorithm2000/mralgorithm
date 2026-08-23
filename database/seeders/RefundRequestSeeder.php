<?php

namespace Database\Seeders;

use App\Enums\RefundRequestStatus;
use App\Models\Purchase;
use App\Models\RefundRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use LogicException;

class RefundRequestSeeder extends Seeder
{
    public function run(): void
    {
        $purchases = Purchase::query()->oldest('id')->limit(12)->get();

        if ($purchases->isEmpty()) {
            throw new LogicException('Purchases must be seeded before refund requests.');
        }

        $admin = User::query()
            ->where('email', config('auth.admin.email'))
            ->first();

        if ($admin === null) {
            throw new LogicException('The administrator must be seeded before refund requests.');
        }

        $statuses = RefundRequestStatus::cases();

        foreach ($purchases as $index => $purchase) {
            $status = $statuses[$index % count($statuses)];
            $requestedAt = now()->subDays($index + 1);
            $isReviewed = $status !== RefundRequestStatus::PENDING;
            $isCompleted = $status === RefundRequestStatus::COMPLETED;

            RefundRequest::updateOrCreate(
                ['purchase_id' => $purchase->id],
                [
                    'status' => $status->value,
                    'reason' => fake()->randomElement([
                        'The service was not delivered.',
                        'The order was placed by mistake.',
                        'The delivered item did not match the description.',
                        'The order took too long to complete.',
                    ]),
                    'requested_at' => $requestedAt,
                    'reviewed_at' => $isReviewed ? $requestedAt->copy()->addHours(6) : null,
                    'completed_at' => $isCompleted ? $requestedAt->copy()->addDay() : null,
                    'admin_id' => $isReviewed ? $admin->id : null,
                    'admin_note' => $isReviewed
                        ? "Seeded {$status->value} refund request."
                        : null,
                ],
            );
        }
    }
}
