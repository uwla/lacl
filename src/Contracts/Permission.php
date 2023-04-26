<?php

namespace Uwla\Lacl\Contracts;

Interface Permission
{
    /**
      * Get permissions by their name
      *
      * @param array<string> $names     The names of the permissions
      * @param mixed         $modelType The class name of the model (optional)
      * @param mixed         $models    The models or their ids (optional)
      * @return \Illuminate\Database\Eloquent\Collection
      */
    public static function getPermissionsByName($names, $modelType=null, $models=null);

    /**
      * Create permissions with the provided names
      *
      * @param array<string> $names
      * @return \Illuminate\Database\Eloquent\Collection
      */
    public static function createPermissionsByName($names);
}
