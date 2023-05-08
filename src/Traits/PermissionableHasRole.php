<?php

namespace Uwla\Lacl\Traits;

use Illuminate\Support\Str;

Trait PermissionableHasRole
{
    use Permissionable, HasRole;

    // override __call from Permissionable and HasPermission
    // in order to avoid conflict
    public function __call($name, $arguments)
    {
        // PART 1
        $pattern = '/^(get|create|delete|grant|attach|revoke)([A-Za-z]+)Permissions?$/';
        $matches = [];
        if (preg_match($pattern, $name, $matches))
        {
            // Here use Str::replace to ensure backward compability with
            // previous interface in order to avoid introducing breakchages.
            // The `attach` methods were replace by `grant`.
            $operation = Str::replace('attach', 'grant', $matches[1]);
            $method = $operation . 'Permission';
            $permission_name = Str::lcfirst($matches[2]);

            if ($permission_name == 'crud')
            {
                $permission_name = ['view', 'update', 'delete'];
                $method = $operation . 'ManyPermissions';
            }

            $arguments[] = $permission_name;
            $arguments[] = $this->getModelId();
            return call_user_func_array(array(self::class, $method), $arguments);
        }

        // PART 2
        $pattern = '/^(add|has|del)PermissionTo([A-Za-z]+)$/';
        $matches = [];
        if (preg_match($pattern, $name, $matches))
        {
            $operation = $matches[1];
            $method = $operation . 'Permission';
            $permission_name = Str::lcfirst($matches[2]);
            $args = [];
            if (empty($arguments))
            {
                $args = [ $permission_name ];
            } else {
                $model = $arguments[0];
                $class = $model::class;
                $id = $model->getModelId();
                $permission_prefix = $model::getPermissionPrefix();
                $permission_name = $permission_prefix . '.' . $permission_name;
                $args = [ $permission_name, $class, $id ];
            }
            return call_user_func_array(array($this, $method), $args);
        }

        // PART 3
        return parent::__call($name, $arguments);
    }
}
