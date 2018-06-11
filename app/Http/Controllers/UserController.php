<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    public function index()
    {
        return User::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'FirstName' => 'required',
            'SecondName' => 'required',
            'Surname' => 'required',
            'SecondSurname' => 'required',
            'Email' => 'required|email|unique:sqlsrv.sec.Users,email',
            'State' => 'required|boolean',
            'Password' => 'required'
        ]);

        $user = new User($request->all());
        $user->save();

        return response()->json($user, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        $user = $id != 'me' ? User::findOrFail($id) : $request->user();
        return  $user;
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
        $user = User::findOrFail($id);
        $request->validate([
            'Email' => 'email',
            'State' => 'boolean',
        ]);

        $user->fill($request->except('Password'));

        if ($request->input('Password')) {
            if ($request->user()->UserId == $user->UserId) {
                $user->Password = bcrypt($request->input('Password'));
            } else {
                return response()->json([
                    'message' => 'Solo puede cambiarle la contraseña a su propio usuario'
                ], 403);
            }
        }

        $user->save();

        return response()->json($user, 200);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json([
            'message' => 'Usuario eliminado correctamente'
        ], 200);
    }
}
