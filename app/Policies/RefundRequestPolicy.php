<?php

namespace App\Policies;

use App\Models\RefundRequest;
use App\Models\User;

class RefundRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('refund_list');
    }

    public function view(User $user, RefundRequest $refundRequest): bool
    {
        return $user->can('refund_view');
    }

    public function updateStatus(User $user, RefundRequest $refundRequest): bool
    {
        return $user->can('refund_status');
    }
}
