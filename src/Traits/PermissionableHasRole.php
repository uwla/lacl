<?php

namespace Uwla\Lacl\Traits;

use Illuminate\Support\Str;

trait PermissionableHasRole
{
    use Permissionable;
    use HasRole;

    /**
     * Call magic method.
     * Override __call from Permissionable and HasPermission to avoid conflict.
     *
     * @param string $name      The name of the method
     * @param array  $arguments The arguments passed
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        // get|create|delete|grant|attach|revoke Permission
        $pattern = '/^(get|create|delete|grant|attach|revoke)([A-Za-z]+)Permissions?$/';
        $matches = [];
        if (preg_match($pattern, $name, $matches)) {
            // Here use Str::replace to ensure backward compability with
            // previous interface in order to avoid introducing breakchages.
            // The `attach` methods were replace by `grant`.
            $operation = Str::replace('attach', 'grant', $matches[1]);
            $method = $operation . 'Permission';
            $permission_name = Str::lcfirst($matches[2]);

            if ($permission_name == 'crud') {
                $permission_name = ['view', 'update', 'delete'];
                $method = $operation . 'ManyPermissions';
            }

            $arguments[] = $permission_name;
            $arguments[] = $this->getModelId();
            return call_user_func_array(array(static::class, $method), $arguments);
        }

        // add|has|del PermissionTo ${Model}
        $pattern = '/^(add|has|del)PermissionTo([A-Za-z]+)$/';
        $matches = [];

        // fallback
        if (! preg_match($pattern, $name, $matches)) {
            return parent::__call($name, $arguments);
        }

        // run the syntax sugared callback
        $operation = $matches[1];
        $method = $operation . 'Permission';
        $permission_name = Str::lcfirst($matches[2]);
        $args = [];
        if (empty($arguments)) {
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

        // FALLBACK
    }
}
