<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\PasswordReset;
use App\Mail\PasswordReset as PasswordResetMail;
use App\Mail\CreateUserMail;
use App\Professional;
use App\ServiceDetail;

use Faker\Factory as Faker;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    public function index(Request $request)
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
            'Surname' => 'required',
            'Email' => 'required|email|unique:sqlsrv.sec.Users,email',
            'State' => 'required|boolean',
            'Password' => 'required'
        ]);
        
        $user = new User($request->all());
        $user->Password = bcrypt($request->input('Password'));
        $user->save();

        $name = $user->FirstName . ' ';
        if ($user->SecondName) {
            $name .= $user->SecondName . ' ';
        }
        $name .= $user->Surname . ' ';
        if ($user->SecondSurname) {
            $name .= $user->SecondSurname;
        }
        
        \Mail::to($user->Email)->send(new CreateUserMail($name, $user->Email, $request->input('Password'), env('CLIENT_URL')));

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
        if ($this->checkEmail($id)) {
            return User::where('Email', $id)->firstOrFail();
        }
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

        $stateUser = $user->State; 

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

        $professional = Professional::where('UserId', $user->UserId)->first();
        if ($request->input('State') == 0 && $stateUser == 1 && $professional) {
            $activeServices = ServiceDetail::where('ProfessionalId', $professional->ProfessionalId)
                ->where('StateId', 1)
                ->first();
            if ($activeServices) {
                return response()->json([
                    'message' => 'Error, El profesional tiene servicios activos'
                ], 400);
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

    private function checkEmail($email) {
        if ( strpos($email, '@') !== false ) {
           $split = explode('@', $email);
           return (strpos($split['1'], '.') !== false ? true : false);
        }
        else {
           return false;
        }
    }

    public function sendEmailToResetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('Email', $request->input('email'))->first();

        if ($user) {
            PasswordReset::where('UserId', $user->UserId)->delete();
            $passwordReset = new PasswordReset();
            $passwordReset->UserId = $user->UserId;
            $faker = Faker::create();
            $passwordReset->Uuid = $faker->uuid;
            $passwordReset->save();
            $name = $user->FirstName . ' ';
            if ($user->SecondName) {
                $name .= $user->SecondName . ' ';
            }
            $name .= $user->Surname . ' ';
            if ($user->SecondSurname) {
                $name .= $user->SecondSurname;
            }
            $url = env('CLIENT_URL') . '/passwordreset/' . $passwordReset->Uuid;
            \Mail::to($user->Email)->send(new PasswordResetMail($name, $url));
        }


        return response()->json([
            'message' => 'Proceso realizado correctamente'
        ], 200);
    }

    public function verifyUuid(Request $request)
    {
        $request->validate([
            'uuid' => 'required'
        ]);

        $passwordReset = PasswordReset::where('Uuid', $request->input('uuid'))->first();

        if ($passwordReset) {
            return response()->json([
                'message' => 'clave válida'
            ], 200);
        }

        return response()->json([
            'message' => 'clave no existe'
        ], 404);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'uuid' => 'required',
            'password' => 'required',
        ]);

        

        $passwordReset = PasswordReset::where('Uuid', $request->input('uuid'))->first();
        if ($passwordReset) {
            $user = User::findOrFail($passwordReset->UserId);
            $user->password = bcrypt($request->input('password'));
            $user->save();
            $passwordReset->where('UserId', $user->UserId)->delete();
            return response()->json([
                'message' => 'Contraseña cambiada con éxito'
            ], 200);
        }

        return response()->json([
            'message' => 'Error, acción no válida'
        ], 400);
    }
}
