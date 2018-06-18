<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Coordinator;
use App\User;

class CoordinatorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Coordinator::with(['documentType', 'gender', 'user'])->get();
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
            'Document' => 'required',
            'DocumentTypeId' => 'required|exists:sqlsrv.cfg.DocumentType,Id',
            'FirstName' => 'required',
            'Surname' => 'required',
            'Email' => 'required|email',
            'GenderId' => 'required|exists:sqlsrv.cfg.Gender,Id',
        ]);
        
        $dataCoordinator = $request->except(['FirstName', 'SecondName', 'Surname', 'SecondSurname', 'Email']);
        $coordinator = new Coordinator($dataCoordinator);
        $user = User::where('Email', $request->input('Email'))->first();
        if (!$user) {
            $user = new User($request->only(['FirstName', 'SecondName', 'Surname', 'SecondSurname', 'Email']));
            $user->save();
        }
        $coordinator->UserId = $user->UserId;
        $coordinator->save();
        $coordinator->load('user');
        return response()->json($coordinator, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $coordinator = Coordinator::with(['documentType', 'gender', 'user'])
            ->findOrFail($id);

        return $coordinator;
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
        $coordinator = Coordinator::findOrfail($id);
        $request->validate([
            'DocumentTypeId' => 'exists:sqlsrv.cfg.DocumentType,Id',
            'GenderId' => 'exists:sqlsrv.cfg.Gender,Id',
        ]);
        
        $dataCoordinator = $request->except(['FirstName', 'SecondName', 'Surname', 'SecondSurname', 'Email']);
        $coordinator->fill($dataCoordinator);
        $user = User::findOrFail($coordinator->UserId);
        $user->fill($request->only(['FirstName', 'SecondName', 'Surname', 'SecondSurname', 'Email']));
        $user->save();
        $coordinator->save();
        $coordinator->load('user');
        return response()->json($coordinator, 201);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $coordinator = Coordinator::findOrFail($id);
        try {
            $coordinator->delete();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error, El paciente no puede ser eliminado, verifique que no tenga datos asociados a este registro'
            ], 400);
        }
        return response()->json([
            'message' => 'Paciente borrado con exito'
        ], 200);
    }
}
