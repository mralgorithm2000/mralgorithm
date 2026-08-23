<?php

namespace App\Policies;

use App\Models\Type;
use App\Models\User;

class TypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('list_types');
    }

    public function view(User $user, Type $type): bool
    {
        return $user->can('list_types');
    }

    public function create(User $user): bool
    {
        return $user->can('add_type');
    }

    public function update(User $user, Type $type): bool
    {
        return $user->can('edit_type');
    }

    public function delete(User $user, Type $type): bool
    {
        return $user->can('delete_type');
    }
}
