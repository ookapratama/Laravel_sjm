<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AccessController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();
        return view('management.access.index', compact('roles', 'permissions'));
    }

public function update(Request $request)
{
    $role = Role::findByName($request->role);
    $role->syncPermissions($request->permissions ?? []);
    return back()->with('success', 'Hak akses diperbarui!');
}

}
