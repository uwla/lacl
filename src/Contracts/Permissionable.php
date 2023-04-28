<?php

namespace Uwla\Lacl\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Uwla\Lacl\Models\Permission;
use Uwla\Lacl\Contracts\HasPermission;

Interface Permissionable
{
    /**
     * Get the id of the model
     *
     * @return mixed
     */
    public function getModelId();
}
