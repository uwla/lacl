<?php

namespace Uwla\Lacl\Models;

use Uwla\Lacl\Traits\PermissionableHasRole;
use Uwla\Lacl\Database\Factories\PermissionFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Permission extends Model
{
    use HasFactory, PermissionableHasRole;

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

    /**
      * Get permissions by their name
      *
      * @param array<string>|string $names     The names of the permissions
      * @param mixed                $modelType The class name of the model (optional)
      * @param mixed                $models    The models or their ids (optional)
      * @return \Illuminate\Database\Eloquent\Collection
      */
    public static function getByName($names, $modelType=null, $models=null)
    {
        if (is_string($names))
            $names = [$names];
        if (! is_array($names))
            throw new InvalidArgumentException('First arg must be string array');

        $n = count($names);
        if ($n == 0)
        {
            throw new InvalidArgumentException('No permission provided');
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

            if (! is_countable($models))
            {
                throw new InvalidArgumentException(
                'Second arguments must be array or Collection.');
            }

            if (count($models) != $n)
            {
                throw new InvalidArgumentException(
                'number of permissions and models must match');
            }

            if ($models instanceof Collection)
            {
                $models = $models->pluck('id');
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
      * Create one permission by the provided name
      *
      * @param  array<string> $names
      * @return \Illuminate\Database\Eloquent\Collection
      */
    public static function createOne($name)
    {
        return self::create(['name' => $name]);
    }

    /**
      * Create permissions by the provided names
      *
      * @param  array<string> $names
      * @return \Illuminate\Database\Eloquent\Collection
      */
    public static function createMany($names)
    {
        if (! is_array($names))
            throw new InvalidArgumentException('Expected string array');
        if (count($names) == 0)
            return new Collection();

        // create permissions
        $permissionsToCreate = [];
        foreach ($names as $name);
            $permissionsToCreate[] = ['name' => $name];
        self::insert($permissionsToCreate); // bulk insertion

        // return them
        return self::whereIn('names', $names)->get();
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
