<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserRole;
use App\User;

class UserRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($user)
    {
        $user = User::findOrFail($user);
        return UserRole::where('UserId', $user->UserId)->with('role')->get();
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $user)
    {
        $roles = $request->input('roles');
        $user = User::findOrFail($user)->UserId;
        \DB::beginTransaction();
        try {
            UserRole::where('UserId', $user)->delete();
            if (count($roles)) {
                $request->validate([
                    'roles.*' => 'exists:sqlsrv.sec.Roles,RoleId'
                ]);
                $usersRoles = [];
                foreach ($roles as $role) {
                    $usersRoles[] = [
                        'UserId' => $user,
                        'RoleId' => $role
                    ]; 
                }
                
                UserRole::insert($usersRoles);

            }

            \DB::commit();
            return response()->json([
                'message' => 'Roles del usuario modificado correctamente'
            ], 200);
        } catch (\Exception $e) {
            \DB::rollback();
            return response()->json([
                'message' => 'Error, no se pudo modificar los roles al usuario, por favor intente de nuevo'
            ], 500);
        }
        
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($user, $id)
    {
        $user = User::findOrFail($user);
        $userRole = UserRole::where('UserId', $user->UserId)
            ->where('UserRoleId', $id)
            ->with('role')
            ->get();
        if (count($userRole) > 0) {
            return $userRole;
        }
        return response()->json([
            ['message' => 'Este usuario no tiene el rol especificado ']
        ], 404);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
