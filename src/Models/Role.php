<?php

namespace Uwla\Lacl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Uwla\Lacl\Contracts\HasPermissionContract;
use Uwla\Lacl\Traits\PermissionableHasRole;

class Role extends Model implements HasPermissionContract
{
    use HasFactory, PermissionableHasRole;
}