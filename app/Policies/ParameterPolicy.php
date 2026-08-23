<?php

namespace App\Policies;

use App\Models\Parameter;
use App\Models\User;

class ParameterPolicy
{
    public function viewOptions(User $user, Parameter $parameter): bool
    {
        return $user->can('good_manage_parameters');
    }

    public function createOption(User $user, Parameter $parameter): bool
    {
        return $user->can('good_manage_parameters');
    }

    public function publishOptionsToPlati(User $user, Parameter $parameter): bool
    {
        return $user->can('good_manage_plati_parameters');
    }

    public function updatePlatiOption(User $user, Parameter $parameter): bool
    {
        return $user->can('good_manage_plati_parameters');
    }

    public function deleteMarketplaceOption(User $user, Parameter $parameter): bool
    {
        return $user->can('good_manage_plati_parameters');
    }
}
