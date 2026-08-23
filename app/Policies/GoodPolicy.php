<?php

namespace App\Policies;

use App\Models\Good;
use App\Models\User;

class GoodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('good_list');
    }

    public function view(User $user, Good $good): bool
    {
        return $user->can('good_list');
    }

    public function viewMarketplaces(User $user, Good $good): bool
    {
        return $user->can('good_view');
    }

    public function create(User $user): bool
    {
        return $user->can('good_add');
    }

    public function update(User $user, Good $good): bool
    {
        return $user->can('good_edit');
    }

    public function publishFixedPriceToDigiseller(User $user, Good $good): bool
    {
        return $user->can('good_add_plati_detail');
    }

    public function publishVariablePriceToDigiseller(User $user, Good $good): bool
    {
        return $user->can('good_add_plati_detail');
    }

    public function manageParameters(User $user, Good $good): bool
    {
        return $user->can('good_manage_parameters');
    }

    public function managePlatiParameters(User $user, Good $good): bool
    {
        return $user->can('good_manage_plati_parameters');
    }

    public function delete(User $user, Good $good): bool
    {
        return $user->can('good_delete');
    }
}
