<?php

namespace Tests\Feature;

use Uwla\Lacl\Database\Seeders\DatabaseSeeder;
use Uwla\Lacl\Http\Controllers\PermissionController;
use Uwla\Lacl\Http\Controllers\RoleController;
use Uwla\Lacl\Http\Controllers\UserController;
use Uwla\Lacl\Models\Permission;
use Uwla\Lacl\Models\Role;
use Uwla\Lacl\Models\User;
use Tests\TestCase;

class ResourcePolicyTest extends TestCase
{
    /**
     * The user making the requests
     *
     * @var \Uwla\Lacl\Models\User
    */
    private $user;

    /**
     * The role of the user
     *
     * @var \Uwla\Lacl\Models\User
    */
    private $role;

    /**
     * Define routes setup.
     *
     * @param  \Illuminate\Routing\Router  $router
     *
     * @return void
     */
    protected function defineRoutes($router)
    {
        $router->group(['middleware' => 'auth:sanctum'], function() use ($router) {
            $router->apiResource('permission', PermissionController::class);
            $router->apiResource('role', RoleController::class);
            $router->apiResource('user', UserController::class);
        });
    }

    /**
     * Set up this test instance
     *
     * @return void
    */
    public function setUp() : void
    {
        parent::setUp();

        // seed the database
        $this->seed(DatabaseSeeder::class);

        // create a user to make the requests
        $this->user = User::factory()->createOne();
        $this->role = Role::factory()->createOne();
        $this->user->addRole($this->role);
    }

    /**
     * Start a http request authenticated as the user
     *
     * @return this
    */
    public function request()
    {
        return $this->actingAs($this->user);
    }

    /**
     * Grant a privilege for making requests
     *
     * @param string $name      The name of the permission
     * @param string $model     The name of the resource type
     * @param int    $model_id  The id of the resource
     * @return void
    */
    public function grant_privilege($name, $model=User::class, $model_id=null)
    {
        // the attributes of the permission
        $attributes = [
            'name' => $name,
            'model' => $model,
            'model_id' => $model_id,
        ];

        // attempt to find existing permission
        $permission = Permission::where($attributes)->first();

        // if permission does not exist, create it for the purpose of testing
        if ($permission == null)
            $permission = Permission::create($attributes);

        // grant the permission privilege to the role
        $this->role->setPermissions([$permission]);
    }

    /**
     * Test authorization for viewing any resource of a kind
     *
     * @return void
    */
    public function test_acl_viewAny()
    {
        // attempt request as unauthorized user
        $response = $this->request()->get('/user');
        $response->assertStatus(403);

        // attempt request as authorized user
        $this->grant_privilege('user.viewAny');
        $response = $this->request()->get('/user');
        $response->assertStatus(200);
    }

    /**
     * Test authorization for viewing specific resource of a kind
     *
     * @return void
    */
    public function test_acl_view()
    {
        // select a random user
        $uid = User::inRandomOrder()->first()->id;

        // attempt request as unauthorized user
        $this->request()->get("/user/{$uid}")->assertStatus(403);

        // attempt request as authorized user
        $this->grant_privilege("user.view", model_id: $uid);
        $this->request()->get("/user/{$uid}")->assertStatus(200);

        // attempt request again, but using different permissions
        $this->grant_privilege('user.viewAny');
        $this->request()->get("/user/{$uid}")->assertStatus(200);
    }

    /**
     * Test authorization for creating a resource of a kind
     *
     * @return void
    */
    public function test_acl_create()
    {
        // generate some attributes using faker
        $attributes = User::factory()->make()->attributesToArray();

        // have to manually add password because it is a hidden attribute
        $attributes['password'] = 'password';

        // attempt request as unauthorized user
        $this->request()->postJson('/user', $attributes)->assertStatus(403);

        // attempt request as authorized user
        $this->grant_privilege('user.create');
        $this->request()->postJson('/user', $attributes)->assertStatus(201);
    }

    /**
     * Test authorization for updating a resource of a kind
     *
     * @return void
    */
    public function test_acl_update()
    {
        // select a random user
        $user = User::inRandomOrder()->first();
        $attributes = $user->attributesToArray();
        $uid = $user->id;

        // have to manually add password because it is a hidden attribute
        $attributes['password'] = 'password';

        // attempt request as unauthorized user
        $this->request()->putJson("/user/{$uid}", $attributes)->assertStatus(403);

        // attempt request as authorized user
        $this->grant_privilege('user.update', model_id: $uid);
        $this->request()->putJson("/user/${uid}", $attributes)->assertStatus(200);

        // attempt request again, but using different permissions
        $this->grant_privilege('user.updateAny');
        $this->request()->putJson("/user/${uid}", $attributes)->assertStatus(200);
    }

    /**
     * Test authorization for deleting a resource of a kind
     *
     * @return void
    */
    public function test_acl_delete()
    {
        // select a random user
        $uid = User::inRandomOrder()->first()->id;

        // attempt request as unauthorized user
        $this->request()->delete("/user/{$uid}")->assertStatus(403);

        // attempt request as authorized user
        $this->grant_privilege("user.delete", model_id: $uid);
        $this->request()->delete("/user/{$uid}")->assertStatus(200);

        // attempt request again, but using different permissions
        $uid = User::inRandomOrder()->first()->id;
        $this->grant_privilege('user.deleteAny');
        $this->request()->delete("/user/{$uid}")->assertStatus(200);
    }
}
