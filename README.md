# LACL - Laravel Access Control List

Implementation of Access Control List System in Laravel.

The system  handles  authorizations  of  certain  actions  based  on  roles  and
permissions. Permissions are assigned  to  roles,  and  roles  are  assigned  to
users. If  a  user's  role  has  the  matching  permission,  then  the  user  is
authorized to perform  the  given  action;  else  the  user  is  forbidden.  The
permissions can be arbitrarily defined by the application developers.

The system can handle resource-based  permissions,  that  is:  a  permission  is
associated with a resource/model/entity in the database. Thus,  it  is  possible
to, for example, define authorization for a user to edit all  articles  or  just
the articles he has created  by  creating  permissions  for  those  articles  in
particular. This is better than adding a 'user_id' column in the articles table.

## Features

- Role-based access control list
- Per-role permission: assign permission to a role
- Per-user permission: assign permission to a user
- Per-model permission: associate a unique model in DB with a permission
- Automated Laravel Policies for controllers using per-model permissions
- Clean interface with permission-name guessing
- Arbitrary permissions
- Arbitrary roles

## Demo

A demo app is available on github at
[uwla/lacl-demo1](https://github.com/uwla/lacl-demo1) to illustrate usage.

## FAQ

**Why should I use  this  package  instead  of  popular  ones,  such  as  spatie
permissions?**

This   package   provides   some   functionality   that   spatie's   and   other
permission-managament packages do not provide, such as per-model permission  and
Resource  Policy  automation.  At  the  same  time,   their   packages   provide
functionality that this package does not provide, such as searching  permissions
based on wildcards or support  for  team  permissions.  Please,  read  the  full
README to understand better what this package does and what it does not. If  you
should use this package or not will  depend  on  the  specific  needs  for  your
application; it is up to you as developer to figure it out.

**Why this package?**

I had specific needs that led me to develop this package and I was not aware  of
another package that would fit my needs at the time I  started  developing  this
package.

## Installation

Install using composer:

```shell
composer require uwla/lacl
```

Publish the ACL table migrations:

```shell
php artisan vendor:publish --provider="Uwla\Lacl\AclServiceProvider"
```

Run the migrations:

```shell
php artisan migrate
```

## Usage

Convention used here:

- `User` refers to `App\Models\User`
- `Role` refers to `Uwla\Lacl\Models\Role`
- `Permission` refers to `Uwla\Lacl\Models\Permission`
- `Collection` refers to `Illuminate\Database\Eloquent\Collection`

### HasRole

Add Traits to the application's user class:

```php
<?php

use Uwla\Lacl\Traits\HasRole;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasRole;

    //
}
```

Add role or multiple roles to user (the roles must already exist):

```php
<?php
// single role
$user->addRole(Role::first());      // using Eloquent model
$user->addRole('administrator');    // using string name

// multiple roles
$user->addRole(Role::all());                        // using Eloquent Collection
$user->addRole(['editor', 'manager', 'senior']);    // using string names
```

Role the role or multiple roles to user (revoking previous ones):

```php
<?php
// single role
$user->setRole(Role::first());      // using Eloquent model
$user->setRole('administrator');    // using string name

// multiple roles
$user->setRole(Role::all());                        // using Eloquent Collection
$user->setRole(['editor', 'manager', 'senior']);    // using string names
```

Get the roles of the user (returns `Collection<Role>` or `array<string>`):

```php
<?php
$user->getRoles();      // get Eloquent models
$user->getRoleNames();  // get string names
```

Delete role (returns `@void`):

```php
<?php
// single role
$user->delRole($role);      // using Eloquent model
$user->delRole('editor');   // using string name

// multiple roles
$user->delRole($roles);                     // using Eloquent Collection
$user->delRole(['editor', 'manager']);      // using strig names

// all roles
$user->delAllRoles();
```

Has role (returns `@bool`):

```php
<?php
// check whether the user has a role
$user->hasRole($role);      // using Eloquent model
$user->hasRole('editor');   // using string name

// check whether the user has all of the given multiple roles
$user->hasRoles($roles);                    // using Eloquent Collection
$user->hasRoles(['editor', 'manager']);     // using strig names

// check whether the user has at least one of the given roles
$user->hasAnyRole($roles);                 // using Eloquent Collection
$user->hasAnyRole(['editor', 'manager']);  // using strig names
```

Count how many roles the user has (returns `@int`):

```php
<?php
$user->countRoles();
```

### HasPermission

Here, we will assign permissions to a  role,  but  they  can  also  be  assigned
directly to a user.

Add a permission or multiple permissions to role (the permissions  must  already
exist):

```php
<?php
// single role
$role->addPermission(Permission::first());      // using Eloquent model
$role->addPermission('manage client emails');   // using string name

// multiple permissions
$role->addPermission(Permission::all());                            // using Eloquent Collection
$role->addPermission(['user.view', 'user.create', 'user.delete']);  // using string names
```

Set the permission or multiple permissions to role (revoking previous ones):

```php
<?php
// single permission
$role->setPermission(Permission::first());       // using Eloquent model
$role->setPermission('manage client emails');    // using string name

// multiple permissions
$role->setPermission(Permission::all());                            // using Eloquent Collection
$role->setPermission(['user.view', 'user.create', 'user.delete']);  // using string names
```

Get the permissions of the role (returns `Collection<Permission>` or `array<string>`):

```php
<?php
$role->getPermissions();      // get Eloquent models
$role->getPermissionNames();  // get string names
```

Delete permission (returns `@void`):

```php
<?php
// single permission
$role->delPermission($permission);  // using Eloquent model
$role->delPermission('view mails'); // using string name

// multiple permissions
$role->delPermission($permissions);               // using Eloquent Collection
$role->delPermission(['user.view', 'user.del']);  // using strig names

// all permissions
$role->delAllPermissions();
```

Has role (returns `@bool`):

```php
<?php
// check whether the role has a permission
$role->hasPermission($permission);    // using Eloquent model
$role->hasPermission('user.view');    // using string name

// check whether the role has all of the given permissions
$role->hasPermissions($permissions);              // using Eloquent Collection
$role->hasPermissions(['user.view', 'user.del']); // using strig names

// check whether the role has at least one of the given permissions
$role->hasAnyPermissions($permissions);               // using Eloquent Collection
$role->hasAnyPermissions(['user.view', 'user.del']);  // using strig names
```

Count how many permissions the role has (returns `@int`):

```php
<?php
$role->countPermissions();
```

### Permissions

Create an arbitrary permission:

```php
<?php
$permission = Permission::create([
  'name' => $customName,
  'description' => $optionalDescription,
]);

// shorter way
$permission = Permission::createOne('View confindential documents');

// or many at once
$permissions = Permission::createMany([
    'view documents', 'edit documents', 'upload files',
]);
```

Create a permission for a given model:

```php
<?php
$article = Article::first();

$permission = Permission::create([
  'name' => 'article.edit',   // can be any name, but standards help automation
  'model' => Article::class,
  'model_id' => $article->id;
]);

// now you could do something like
$user->add($permission);
$user->hasPermission('article.edit', Article::class, $article->id); // true
```

You can perform any operations on `Permission` that are  supported  by  Eloquent
models, such as deleting, updating, fetching, searching, etc.

### Per-model permission

The Trait `Permissionable` provides an interface for managing  CRUD  permissions
associated with a given model. In  the  following  examples,  we  will  use  the
`Article` model to illustrate how would we manage per-article permissions.

First, make sure the class do use the trait.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Uwla\Lacl\Traits\Permissionable;

class Article extends Model
{
    use Permissionable;
}
```

**OBS**: If your model needs to use both `Permissionable` and `HasRole`  traits,
then you will be better off using the `PermissionableHasRole`  trait,  which  is
basically a mix of the both that solves a method conflict between the two.  That
could be the case for the `User` class, which could have roles and at  the  same
time be a permissionable model.

Here is a summary of the auxilary methods provided by `Permissionable`:

| Name                       | Description                                                            |
| :------------------------- | :--------------------------------------------------------------------- |
| `createViewPermission`     | Create permission for viewing the model.                               |
| `createUpdatePermission`   | Create permission for updating the model.                              |
| `createDeletePermission`   | Create permission for deleting the model.                              |
| `createCrudPermissions`    | Create permissions above.                                              |
| `getViewPermission`        | Get the permission for viewing the model.                              |
| `getUpdatePermission`      | Get the permission for updating the model.                             |
| `getDeletePermission`      | Get the permission for deleting the model.                             |
| `getCrudPermissions`       | Get the permissions above.                                             |
| `deleteViewPermission`     | Delete the permission for viewing the model.                           |
| `deleteUpdatePermission`   | Delete the permission for updating the model.                          |
| `deleteDeletePermission`   | Delete the permission for deleting the model.                          |
| `deleteCrudPermissions`    | Delete the permissions above.                                          |
| `grantViewPermission`      | Grant the permission for viewing the model to the given user/role.     |
| `grantUpdatePermission`    | Grant the permission for updating the model to the given user/role.    |
| `grantDeletePermission`    | Grant the permission for deleting the model to the given user/role.    |
| `grantCrudPermissions`     | Grant the permissions above to the given user/role.                    |
| `revokeViewPermission`     | Revoke the permission for viewing the model from the given user/role.  |
| `revokeUpdatePermission`   | Revoke the permission for updating the model from the given user/role. |
| `revokeDeletePermission`   | Revoke the permission for deleting the model from the given user/role. |
| `revokeCrudPermissions`    | Revoke the permissions above from the given user/role.                 |

As you can see, the per-model permissions are 'view',  'update',  and  'delete'.
This is because the most generic actions a user can perform on  a  single  model
is to view it, update it, or delete  it.  It  also  facilitates  automation  and
integration with Laravel Policies.

The create-permission  helpers  will  either  fetch  from  or  insert  into  the
database the associated permission, depending on whether it  already  exists  or
not. The get-permissions helpers assume the permission exists in  DB,  and  then
try to fetch. The delete-permission helpers will try to delete  the  permissions
in DB, but does not assume they already exist.  The  grant  permmission  helpers
will assign the permissions to  the  user  or  to  the  given  user/role,  which
assumes the permissions already exist (if they don't exist,  an  Error  will  be
thrown). The revoke-permission helpers try to revoke  the  permission  from  the
user/role; it  assumes  the  permissions  exist  but  it  does  not  assume  the
user/role has access to those permissions.

Create crud permission (or fetch them, if already exist) for the article:

```php
<?php
$article = Article::find(1);
$viewPermission = $article->createViewPermission();
$updatePermission = $article->createUpdatePermission();
$deletePermission = $article->createDeletePermission();

// or, more simply
$crudPermissions = $article->createCrudPermissions();
```

Get the permissions, assuming they were already created before:

```php
<?php
$article = Article::find(1);
$viewPermission = $article->getViewPermission();
$updatePermission = $article->getUpdatePermission();
$deletePermission = $article->getDeletePermission();

// or, more simply
$crudPermissions = $article->getCrudPermissions();
```

Delete the permissions (they may exist or not).

```php
<?php
$article = Article::find(1);
$article->deleteViewPermission();
$article->deleteUpdatePermission();
$article->deleteDeletePermission();

// or, more simply
$article->deleteCrudPermissions();
```

Grant the permissions to the user:

```php
<?php
// you can fetch the permissions manually and then grant it to the user or role
$viewPermission = $article->getViewPermission();
$role->addPermission($viewPermission); // assign to a role
$user->addPermission($viewPermission); // assign to a specific user

$crudPermissions = $article->getCrudPermissions();
$user->addPermissions($crudPermissions);
$role->addPermissions($crudPermissions);

// but it is easier to grant them via the model
$article->grantViewPermission($role);
$article->grantViewPermission($user);

// grant all crud permissions to the given user/role
$article->grantCrudPermissions($user);
$article->grantCrudPermissions($role);
```

Revoking permissions is done in the same way:

```php
<?php
// you could fetch the permissions manually, then revoke from the user or role
$viewPermission = $article->getViewPermission();
$role->delPermission($viewPermission); // revoke from a role
$user->delPermission($viewPermission); // revoke from a specific user

$crudPermissions = $article->getCrudPermissions();
$user->delPermissions($crudPermissions);
$role->delPermissions($crudPermissions);

// it is easier to revoke them via the model
$article->revokeViewPermission($role);
$article->revokeViewPermission($user);

// revoke all crud permissions to the given user/role
$article->revokeCrudPermissions($user);
$article->revokeCrudPermissions($role);
```

For now, to check if the user has a permission to view/update/delete the model,
you could do the following:

```php
<?php

if ($user->hasPermission($article->getViewPermission())
{
    // user can view the article
    return new Response(['data' => $article]);
}

if ($user->hasPermission($article->getDeletePermission())
{
    // user can delete the article
    $article->delete();
    return new Response(['success' => true]);
}
```

Also, it is important to remember that the user permissions are all  permissions
assigned specifically to him plus the permissions assigned to any role  he  has.
Therefore, it the user does not  have  a  direct  permission  to  the  view  the
article, but one of its role has, the user will also have that permission.

### Per-model permission deletion

To delete all per-model permissions associated with a model,
you can use the `deletetThisModelPermissions` method that comes
with the `Permissionable` trait.

```php
<?php
$model->deletetThisModelPermissions();
```

If you want that behavior to  be  triggered  automatically  before  deleting  an
Eloquent model, you can add that to the `boot` method of your model:

```php
<?php
/*
 * Register callback to delete permissions associated with this  model  when  it
 * gets deleted.
 *
 * @return void
 */
protected static function boot() {
    parent::boot();
    static::deleted(function($model) {
        Permission::where([
            'model' => $model::class,
            'model_id' => $model->id,
        ])->delete();
    });
}
```

Just keep in mind that  mass  deletions  do  not  trigger  the  `static:deleted`
because when you use Eloquent Models for mass deletion it  will  not  fetch  the
models first and later deleted them  one  by  one;  it  will,  instead,  send  a
deletion query to the database and that is.

### Per-model permission dynamically

There is a shorter, cleaner way (aka, syntax sugar) to deal with permissions:

```php
<?php
// create a permission to send emails
Permission::create(['name' => 'sendEmails']);

// you can do the following,
// a shorter way to add, check, and del a single permission
$user->addPermissionToSendEmails();
$user->hasPermissionToSendEmails(); // true
$user->delPermissionToSendEmails();
$user->hasPermissionToSendEmails(); // false
```

Whenever you call a method that it is undefined, it will trigger PHP's  language
construct `__calll`  method,  which  allows  us  programmers  to  define  custom
behavior to handle undefined methods. In this case,  the  `HasPermission`  trait
handles custom behavior in the following way:

1. If the method name does not start with `hasPermissionTo`,  `addPermissionTo`,
   or `delPermissionTo`, then it will call the `parent::__call` to handle it.
2. The remaining of the method name  (in  this  case,  it  is  `SendEmails`)  is
   passed to a method called `guessPermissionName`, which you are encouraged  to
   overwrite to fit your needs.
3. By default, `guessPermissionName` will just lower case the  first  letter  of
   the remaining method name.
4. It will then call one  of  the  following  `hasPermission`,  `addPermission`,
   `delPermission`, depending on the method name.
5. If an argument is passed,  it  is  assumed  to  be  a  class  that  uses  the
   `Permissionable` trait, provided  by  this  package.  The  permission  to  be
   create/checked/delete will be a model-based permission

The default convention  is  that  the  permission  name  is  prefixed  with  the
`<model>.`, where `<model>` is the lowercase name of the model's class. This  is
the current convention, but it will be customizable in the near future.

Here is an example with models:

```php
<?php
$user->addPermissionToView($article);
$user->hasPermissionToView($article);           // true
$user->delPermissionToView($article);
$user->addPermissionToDeleteForever($article);
$user->hasPermissionToView($article);           // false, since we deleted it
$user->hasPermissionToDeleteForever($article);  // true

// of course, this works for roles too
$role->addPermissionToUpdate($article);
```

Notice that before adding a permission, the permission should already exist.  If
the permission does not exist, you should create it.

### Per-model permission model fetching

Here is how to fetch the models of a specific type that the user or a role has
access to:

```php
<?php
// per user
$articles = $user->getModels(Article::class);

// per role
$articles = $role->getModels(Article::class);
```

This will fetch all `Article` models such that there is a  per-model  permission
associated with them and the user or role has access to at  least  one  of  such
per-model permissions.

You can specify the name of the permission too:

```php
<?php
// get all articles this user can view
$articles = $user->getModels(Article::class, 'view');

// get all articles this user can edit
$articles = $user->getModels(Article::class, 'update');

// get all the users this role can delete
$users = $role->getModels(User::class, 'delete');

// get all products this user is able to cancel the delivery of
$products = $user->getModels(Product::class, 'cancelDelivery');
```

That way, you have granular control to fetch the models each user  or  role  has
permission to access, filtering by a particular action (aka, permission name).

### Generic model permissions

Generic model permissions are permissions to access all instances  of  a  model.
This  is  different  from  per-model  permission,  which  handles  access  to  a
particular instance of a model.

Those generic  model  permissions  are  `create`,  `viewAny`,  `updateAny`,  and
`deleteAny`. They are designed to follow the standards of Laravel  Policies.  Of
course, you can redfine those and use the custom names you want, but it is  more
convenient to stick to those convetions because we can  automate  tasks  instead
of manually defining custom names.

The interface for generic model permissions are the same as  for  the  per-model
permission, the only difference is that the methods are static.

```php
<?php
// so, instead of
$article->createCrudPermissions();
$article->deleteUpdatePermission();
$article->grantDeletePermission($user);

// we basically do:
Article::createCrudPermissions();
Article::deleteUpdateAnyPermission();
Article::grantDeleteAnyPermission($user);
```

In the second example above, the user would be able to delete all articles
since he was granted permission to `deleteAny` any article model.

Everything that was explained about per-model permissions applies to
generic model permissions: creation, fetching, deletion, granting, revoking,
dynamic names, etc. There are only three differences:

1. The permissions must be created, fetched, and deleted using static methods.
2. The permissions grant access to all models, not just one.
3. The permission names end with the `Any`, such as `updateAny`, except for the
   `create` permission.

Actually, there is also more two exceptions. First, to delete all generic model
permissions:

```php
<?php
Article::deletetGenericModelPermissions();
```

To delete all model permissions, both generic model  permissions  and  per-model
permissions (be careful with this one, since it will delete all of them):

```php
<?php
Article::deletetAllModelPermissions();
```

### Resource Policy

This package provides the `ResourcePolicy` trait to  automate  Laravel  Policies
using a standard convention for creating permissions.

The convention is:

- To create a model, the user must have the `{model}.create` permission.
- To view all models, the user must have the `{model}.viewAny` permission.
- To view a specific model, the user  must  have  either  the  `{model}.viewAny`
  permission or the `{model}.view` per-model permission for the specific model.
- To update a specific model, the user must have either the `{model}.updateAny`
  permission or the `{model}.update` per-model permission for the specific model.
- To delete a specific model, the user must have either the `{model}.deleteAny`
  permission or the `{model}.delete` per-model permission for the specific model.
- To force-delete a specific model, the user must have either the `{model}.forceDeleteAny`
  permission or the `{model}.forceDelete` per-model permission for the specific model.
- To restore a specific model, the user must have either the `{model}.restoreAny`
  permission or the `{model}.restore` per-model permission for the specific model.

Where `{model}` is the lowercase name of the model's classname. For example,  if
it is the `App\Models\User`, it would be `user`; if it is  `App\Models\Product`,
it would be `product`.

Here is how you would use it for a `ArticlePolicy`:

```php
<?php

namespace App\Policies;

use App\Models\Article;
use Uwla\Lacl\Traits\ResourcePolicy;
use Uwla\Lacl\Contracts\ResourcePolicy as ResourcePolicyContract;

class ArticlePolicy implements ResourcePolicyContract
{
    use ResourcePolicy;

    public function getResourceModel()
    {
        return Article::class;
    }
}
```

Then, in the `ArticleController`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Models\Article;
use Illuminate\Http\Response;

class ArticleController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Article::class, 'article');
    }

    public function index(): Response
    {
        return new Response(Article::all());
    }

    public function store(StoreArticleRequest $request): Response
    {
        $article = Article::create($request->all());
        return new Response($article);
    }

    public function show(Article $article): Response
    {
        return new Response($article);
    }

    public function update(UpdateArticleRequest $request, Article $article): Response
    {
        $article->update($request->all());
        return new Response($article);
    }

    public function destroy(Article $article): Response
    {
        return new Response($article);
    }
}
```

The Laravel Policies  are  triggered  before  the  request  is  handled  to  the
controller. Since we are using the `ResourcePolicy`, before the request is  sent
to the `ArticleController`, our application will  check  if  the  user  has  the
permission to perform the action associated with the method. The  goal  here  is
to have an automated process of  Access  Control,  freeing  the  developer  from
having to manually check if the user has the permission to  perform  the  common
CRUD operations.

## Overriding Permissions and Role models

It is possible to override the models which are used by the traits `HasRole`,
`HasPermission`, `Permissionable`, `PermissionableHasRole`.

To do so, just override the protected static methods `Permission` and `Role`:

```php
<?php

protected static function Permission()
{
    return CustomPermission::class;
}

protected static function Role()
{
    return CustomRole::class;
}
```

## Contributions

Contributions are welcome. Fork the repository, make your changes, then  make  a
pull request.

## HELP

If you any need help, feel free to open an issue on this package's github repo.
