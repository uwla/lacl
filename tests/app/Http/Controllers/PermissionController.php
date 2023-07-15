<?php

namespace Tests\App\Http\Controllers;

use Tests\App\Http\Requests\StorePermissionRequest;
use Tests\App\Http\Requests\UpdatePermissionRequest;
use Tests\App\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Create the controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->authorizeResource(Permission::class, 'permission');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Permission::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Uwla\Lacl\Http\Requests\StorePermissionRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePermissionRequest $request)
    {
        return Permission::create($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  \Uwla\Lacl\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function show(Permission $permission)
    {
        return $permission;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Uwla\Lacl\Http\Requests\UpdatePermissionRequest  $request
     * @param  \Uwla\Lacl\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        $permission->update($request->all());
        return $permission;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Uwla\Lacl\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();
        return $permission;
    }
}
