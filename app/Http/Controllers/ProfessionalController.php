<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Professional;
use App\User;

class ProfessionalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Professional::with(['gender', 'specialty', 'documentType', 'accountType', 'user'])->get();
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
            'SpecialtyId' => 'required|exists:sqlsrv.cfg.Specialty,Id',
            'Address' => 'required',
            'Telephone1' => 'required',
            'CodeBank' => 'required',
            'AccountTypeId' => 'required|exists:sqlsrv.cfg.AccountType,Id',
            'AccountNumber' => 'required' 
        ]);
        
        $dataProfessional = $request->except(['FirstName', 'SecondName', 'Surname', 'SecondSurname', 'Email']);
        $professional = new Professional($dataProfessional);
        $user = User::where('Email', $request->input('Email'))->first();
        $dataUser = $request->only(['FirstName', 'SecondName', 'Surname', 'SecondSurname', 'Email']);
        if (!$user) {
            $user = new User($dataUser);
            $user->Password = bcrypt('12345');
        } else {
            $user->fill($dataUser);
        }
        $user->save();
        $professional->UserId = $user->UserId;
        $professional->save();
        $professional->load('user');
        return response()->json($professional, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $professional = Professional::with(['gender', 'specialty', 'documentType', 'accountType', 'user'])
            ->findOrFail($id);

        return $professional;
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
        $professional = Professional::findOrfail($id);
        $request->validate([
            'DocumentTypeId' => 'exists:sqlsrv.cfg.DocumentType,Id',
            'Email' => 'email',
            'GenderId' => 'exists:sqlsrv.cfg.Gender,Id',
            'SpecialtyId' => 'exists:sqlsrv.cfg.Specialty,Id',
            'AccountTypeId' => 'exists:sqlsrv.cfg.AccountType,Id',
        ]);
        
        $dataProfessional = $request->except(['FirstName', 'SecondName', 'Surname', 'SecondSurname', 'Email']);
        $professional->fill($dataProfessional);
        $user = User::findOrFail($professional->UserId);
        $user->fill($request->only(['FirstName', 'SecondName', 'Surname', 'SecondSurname', 'Email']));
        $user->save();
        $professional->save();
        $professional->load('user');
        return response()->json($professional, 201);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $professioanl = Professional::findOrFail($id);
        try {
            $professioanl->delete();
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
