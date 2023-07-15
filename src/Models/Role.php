<?php

namespace Uwla\Lacl\Models;

use Uwla\Lacl\Contracts\HasPermissionContract;
use Uwla\Lacl\Traits\PermissionableHasRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model implements HasPermissionContract
{
    use HasFactory, PermissionableHasRole;
}
