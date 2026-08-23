<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('supplier_list');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->can('supplier_list');
    }

    public function create(User $user): bool
    {
        return $user->can('supplier_add');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->can('supplier_edit');
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->can('supplier_delete');
    }
}
