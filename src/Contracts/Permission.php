<?php

namespace Uwla\Lacl\Contracts;

Interface Permission
{
    public static function getPermissionsByName($names, $modelType, $models);
}
