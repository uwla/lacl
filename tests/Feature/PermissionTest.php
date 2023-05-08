<?php

namespace Tests\Feature;

use Tests\TestCase;
use Uwla\Lacl\Models\Permission;

class PermissionTest extends TestCase
{
    public function test_get_permissions_by_name()
    {
        Permission::insert([
            [
                'name' => 'article.viewAny',
                'model' => null
            ],
            [
                'name' => 'article.updateAny',
                'model' => null
            ],
            [
                'name' => 'article.viewAny',
                'model' => 'Article'
            ],
            [
                'name' => 'article.updateAny',
                'model' => 'Article'
            ],
        ]);

        $arr = ['article.viewAny', 'article.updateAny'];
        $p = Permission::getByName($arr);
        $p = $p->filter(fn($el) => in_array($el->name, $arr));
        $this->assertTrue($p->count() == 4);

        $m = 'Article';
        $p = Permission::getByName($arr, $m);
        $p = $p->filter(fn($el) => in_array($el->name, $arr) && $el->model == $m);
        $this->assertTrue($p->count() == 2);
    }

    public function test_create_permissions_by_name()
    {
        $names = [
            'manage users', 'dispatch jobs', 'delete articles', 'upload files',
            'create teams', 'view orders', 'edit roles',
        ];
        $created = Permission::createMany($names);
        $found = Permission::whereIn('name', $names)->get();
        $this->assertTrue($created->diff($found)->isEmpty());
    }
}

