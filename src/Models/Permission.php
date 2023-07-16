<?php

namespace Uwla\Lacl\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Uwla\Lacl\Traits\Permissionable;

class Permission extends Model
{
    use HasFactory, Permissionable;

    /**
      * Get the instances of the given model which have this permission
      *
      * @param string $model_class
      * @param string $id_column
      * @return \Illuminate\Database\Eloquent\Collection
      */
    public function getModels($model_class, $id_column)
    {
        $ids = PermissionModel::where([
            'permission_id' => $this->id,
            'model' => $model_class,
        ])->pluck('id');
        $models = $model_class::whereIn($id_column, $ids)->get();
        return $models;
    }

    /**
      * Get the roles that have this permission
      *
      * @return \Illuminate\Database\Eloquent\Collection
      */
    public function getRoles()
    {
        return $this->getModels($this::Role(), 'id');
    }

    /**
      * Get the name of the roles that have this permission
      *
      * @return \Illuminate\Database\Eloquent\Collection
      */
    public function getRoleNames()
    {
        return $this->getRoles()->pluck('name');
    }

    /**
      * Get permissions by their name
      *
      * @param array<string>|string $names  The names of the permissions
      * @param mixed $modelType             The class name of the model (optional)
      * @param mixed $models                The models or their ids (optional)
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
            throw new InvalidArgumentException('No permission provided');

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
        return static::create(['name' => $name]);
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
        static::insert($permissionsToCreate); // bulk insertion

        // return them
        return static::whereIn('names', $names)->get();
    }
}
