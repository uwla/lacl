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
- Arbitrary permissions
- Arbitrary roles

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
// single role
$user->addRole(Role::first());      // using Eloquent model
$user->addRole('administrator');    // using string name

// multiple roles
$user->addRole(Role::all());                        // using Eloquent Collection
$user->addRole(['editor', 'manager', 'senior']);    // using string names
```

Role the role or multiple roles to user (revoking previous ones):

```php
// single role
$user->setRole(Role::first());      // using Eloquent model
$user->setRole('administrator');    // using string name

// multiple roles
$user->setRole(Role::all());                        // using Eloquent Collection
$user->setRole(['editor', 'manager', 'senior']);    // using string names
```

Get the roles of the user (returns `Collection<Role>` or `array<string>`):

```php
$user->getRoles();      // get Eloquent models
$user->getRoleNames();  // get string names
```

Delete role (returns `@void`):

```php
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
$user->countRoles();
```

### HasPermission

Here, we will assign permissions to a  role,  but  they  can  also  be  assigned
directly to a user.

Add a permission or multiple permissions to role (the permissions  must  already
exist):

```php
// single role
$role->addPermission(Permission::first());      // using Eloquent model
$role->addPermission('manage client emails');   // using string name

// multiple permissions
$role->addPermission(Permission::all());                            // using Eloquent Collection
$role->addPermission(['user.view', 'user.create', 'user.delete']);  // using string names
```

Set the permission or multiple permissions to role (revoking previous ones):

```php
// single permission
$role->setPermission(Permission::first());       // using Eloquent model
$role->setPermission('manage client emails');    // using string name

// multiple permissions
$role->setPermission(Permission::all());                            // using Eloquent Collection
$role->setPermission(['user.view', 'user.create', 'user.delete']);  // using string names
```

Get the permissions of the role (returns `Collection<Permission>` or `array<string>`):

```php
$role->getPermissions();      // get Eloquent models
$role->getPermissionNames();  // get string names
```

Delete permission (returns `@void`):

```php
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
$role->countPermissions();
```

### Permissions

Create an arbitrary permission:

```php
$permission = Permission::create([
  'name' => $customName,
  'description' => $optionalDescription,
])
```

Create a permission for a given model:

```php
$article = Article::first();

$permission = Permission::create([
  'name' => 'article.edit',   // can be any name, but standards help automation
  'model' => Article::class,
  'model_id' => $article->id;
])

// now you could do something like
$user->add($permission);
$user->hasPermission('article.edit', Article::class, $article->id); // will return true
```

You can perform any operations on `Permission` that are  supported  by  Eloquent
models, such as deleting, updating, fetching, searching, etc.

## Roadmap

A list of intended features to add:

- demo app
- maybe blade components
