<?php

namespace Uwla\Lacl\Models;

use Uwla\Lacl\Traits\HasRole;
use Uwla\Lacl\Database\Factories\PermissionFactory;
use Uwla\Lacl\Contracts\Permission as PermissionContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Permission extends Model implements PermissionContract
{
    use HasFactory, HasRole;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    public $fillable = [
        'name',
        'model',
        'model_id',
        'description'
    ];

    public static function getPermissionsByName($names, $modelType, $models)
    {
        $n = count($names);
        if ($n == 0)
        {
            throw new InvalidArgumentException(
            "No permission provided");
        }

        if ($modelType != null)
            $query = Permission::where('model', $modelType);
        else
            $query = Permission::query();

        if ($models == null) {
            // we are dealing with permissions for a resource group
            $query->whereIn('name', $names);
        } else {
            // we are dealing with permission for specific resources
            if (count($models) != $n)
            {
                throw new InvalidArgumentException(
                "number of permissions and models must match");
            }

            if ($models[0] instanceof Model)
            {
                $models = $models->pluck('id')->toArray();
            }

            // each resource is identified by its model_id
            $query->where(function($q) use ($names, $models, $n)
            {
                for ($i = 0; $i < $n; $i+=1)
                {
                    $q->orWhere([
                        ['name', $names[$i]],
                        ['model_id', $models[$i]],
                    ]);
                }
            });
        }

        return $query->get();
    }


    /**
     * Create a new factory instance for the model.
     * This is used for testing. End-users are encouraged to change it.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return PermissionFactory::new();
    }
}
