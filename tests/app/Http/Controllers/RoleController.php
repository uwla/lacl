<?php

namespace Tests\App\Http\Controllers;

use Tests\App\Http\Requests\StoreRoleRequest;
use Tests\App\Http\Requests\UpdateRoleRequest;
use Tests\App\Models\Role;

class RoleController extends Controller
{

    /**
     * Create the controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->authorizeResource(Role::class, 'role');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Role::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Uwla\Lacl\Http\Requests\StoreRoleRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRoleRequest $request)
    {
        return Role::create($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  \Uwla\Lacl\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        return $role;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Uwla\Lacl\Http\Requests\UpdateRoleRequest  $request
     * @param  \Uwla\Lacl\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        $role->update($request->all());
        return $role;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Uwla\Lacl\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role)
    {
        $role->delete();
        return $role;
    }
}
