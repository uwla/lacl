<?php

namespace Uwla\Lacl\Contracts;

Interface Permissionable
{
    /**
     * Get the id of the model
     *
     * @return mixed
     */
    public function getModelId();

    /**
     * Format the name of the permission associated with this model.
     *
     * @param string $permissionName
     * @return void
     */
    public static function getPermissionPrefix();
}
