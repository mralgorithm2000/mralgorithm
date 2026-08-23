<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('role_list');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('role_list');
    }

    public function create(User $user): bool
    {
        return $user->can('role_add');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('role_edit');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('role_delete');
    }
}
