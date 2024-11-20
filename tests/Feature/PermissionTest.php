<?php

namespace Tests\Feature;

use Tests\App\Models\Role;
use Tests\App\Models\User;
use Tests\TestCase;
use Tests\App\Models\Permission;

class PermissionTest extends TestCase
{
    public function test_get_permissions_by_name()
    {
        Permission::insert([
            [
                'name' => 'article.viewAny',
                'model_type' => null
            ],
            [
                'name' => 'article.updateAny',
                'model_type' => null
            ],
            [
                'name' => 'article.viewAny',
                'model_type' => 'Article'
            ],
            [
                'name' => 'article.updateAny',
                'model_type' => 'Article'
            ],
        ]);

        $arr = ['article.viewAny', 'article.updateAny'];
        $p = Permission::getByName($arr);
        $p = $p->filter(fn($el) => in_array($el->name, $arr));
        $this->assertTrue($p->count() == 4);

        $m = 'Article';
        $p = Permission::getByName($arr, $m);
        $p = $p->filter(fn($el) => in_array($el->name, $arr) && $el->model_type == $m);
        $this->assertTrue($p->count() == 2);
    }

    public function test_create_permissions_by_name()
    {
        $names = [
            'manage users',
            'dispatch jobs',
            'delete articles',
            'upload files',
            'create teams',
            'view orders',
            'edit roles',
        ];
        $created = Permission::createMany($names);
        $found = Permission::whereIn('name', $names)->get();
        $this->assertTrue($created->diff($found)->isEmpty());
    }

    public function test_get_models_associated_with_permission()
    {
        $permission = Permission::factory()->createOne();

        // test with roles
        $roles = Role::factory(5)->create();
        Role::addPermissionToMany($permission, $roles);
        $models = $permission->getRoles();
        $this->assertTrue($models->diff($roles)->isEmpty());

        // test with generic model
        $users = User::factory(30)->create();
        User::addPermissionToMany($permission, $users);
        $models = $permission->getModels(User::class, 'id');
        $this->assertTrue($models->diff($users)->isEmpty());
    }
}