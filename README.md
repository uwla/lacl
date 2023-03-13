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
use Uwla\Lacl\Traits\HasPermission;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasPermission, HasRole;

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
$user->hasAnyRoles($roles);                 // using Eloquent Collection
$user->hasAnyRoles(['editor', 'manager']);  // using strig names
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
])
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

### Per model permissions

The Trait `Permissionable` provides an interface for managing  CRUD  permissions
associated with a given model. In  the  following  examples,  we  will  use  the
`Article` model to illustrate how would we manage per-article permissions.

First, make sure the class do use the trait.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Uwla\Lacl\Contracts\Permissionable as PermissionableContract;
use Uwla\Lacl\Traits\Permissionable;

class Article extends Model implements PermissionableContract
{
    use Permissionable;
}
```

Here is a summary of the auxilary methods provided by `Permissionable`:

| Name                       | Description                                                                        |
| :------------------------- | :----------------------------------------------------------------------------      |
| `createViewPermission`     | Create permission for viewing the model.                                           |
| `createUpdatingPermission` | Create permission for updating the model.                                          |
| `createDeletePermission`   | Create permission for deleting the model.                                          |
| `createCrudPermissions`    | Create permissions to view, update, delete the model.                              |
| `getViewPermission`        | Get the permission for viewing the model.                                          |
| `getUpdatingPermission`    | Get the permission for updating the model.                                         |
| `getDeletePermission`      | Get the permission for deleting the model.                                         |
| `getCrudPermissions`       | Get the permissions to view, update, delete the model.                             |
| `deleteViewPermission`     | Delete the permission for viewing the model.                                       |
| `deleteUpdatingPermission` | Delete the permission for updating the model.                                      |
| `deleteDeletePermission`   | Delete the permission for deleting the model.                                      |
| `deleteCrudPermissions`    | Delete the permissions to view, update, delete the model.                          |
| `attachViewPermission`     | Attach the permission for viewing the model to the given user/role.                |
| `attachUpdatingPermission` | Attach the permission for updating the model to the given user/role.               |
| `attachDeletePermission`   | Attach the permission for deleting the model to the given user/role.               |
| `attachCrudPermissions`    | Attach the permissions to view, update, delete the model to the given user/role.   |
| `revokeViewPermission`     | Revoke the permission for viewing the model from the given user/role.              |
| `revokeUpdatingPermission` | Revoke the permission for updating the model from the given user/role.             |
| `revokeDeletePermission`   | Revoke the permission for deleting the model from the given user/role.             |
| `revokeCrudPermissions`    | Revoke the permissions to view, update, delete the model from the given user/role. |

As you can see, the per-model permissions are 'view',  'update',  and  'delete'.
This is because the most generic actions a user can perform on  a  single  model
is to view it, update it, or delete  it.  It  also  facilitates  automation  and
integration with Laravel Policies.

The create-permission  helpers  will  either  fetch  from  or  insert  into  the
database the associated permission, depending on whether it  already  exists  or
not. The get-permissions helpers assume the permission exists in  DB,  and  then
try to fetch. The delete-permission helpers will try to delete  the  permissions
in DB, but does not assume they already exist.  The  attach-permmission  helpers
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

Attach the permissions to the user:

```php
<?php
// while you could fetch the permissions manually and then attach it to the user or role
$viewPermission = $article->getViewPermission();
$role->addPermission($viewPermission); // assign to a role
$user->addPermission($viewPermission); // assign to a specific user

$crudPermissions = $article->getCrudPermissions();
$user->addPermissions($crudPermissions);
$role->addPermissions($crudPermissions);

// it is easier to attach them via the model
$article->attachViewPermission($role);
$article->attachViewPermission($user);

// attach all crud permissions to the given user/role
$article->attachCrudPermissions($user);
$article->attachCrudPermissions($role);
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

### Dynamic permissions

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
5. If an argument is passed, it is assumed to be a  class  that  implements  the
   `PermissionableContract`, provided by this  package.  The  permission  to  be
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

## Roadmap

A list of intended things to do:

- add feature to get the models the user has permission to access
- more tests for checking deletion of models and permissions
