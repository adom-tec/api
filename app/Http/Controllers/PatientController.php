<?php
	
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Patient;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Patient::limit(20)->with(['documentType', 'unitTime', 'gender', 'patientType'])->get();
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
            'Age' => 'required|numeric',
            'UnitTimeId' => 'required|exists:sqlsrv.cfg.UnitTime,Id',
            'FirstName' => 'required',
            'Surname' => 'required',
            'Email' => 'nullable|email',
            'GenderId' => 'required|exists:sqlsrv.cfg.Gender,Id',
            'Address' => 'required',
            'Telephone1' => 'required',
            'PatientTypeId' => 'required|exists:sqlsrv.cfg.PatientType,Id',
            'AttendantEmail' => 'nullable|email'
        ]);

        $patient = new Patient($request->all());
        $nameComplete = $request->input('FirstName') . ' ';
        
        if ($request->input('SecondName')) {
            $nameComplete .= $request->input('SecondName') . ' ';
        }
        $nameComplete .= $request->input('Surname') . ' ';
        if ($request->input('SecondSurname')) {
            $nameComplete .= $request->input('SecondSurname');
        }
        $patient->NameCompleted = $nameComplete;
        $patient->save();
        return response()->json($patient, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $patient = Patient::with(['documentType', 'unitTime', 'gender', 'patientType'])
            ->findOrFail($id);

        return $patient;
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
        $patient =  Patient::findOrFail($id);

        $request->validate([
            'DocumentTypeId' => 'exists:sqlsrv.cfg.DocumentType,Id',
            'Age' => 'numeric',
            'UnitTimeId' => 'exists:sqlsrv.cfg.UnitTime,Id',
            'Email' => 'nullable|email',
            'GenderId' => 'exists:sqlsrv.cfg.Gender,Id',
            'PatientTypeId' => 'exists:sqlsrv.cfg.PatientType,Id',
            'AttendantEmail' => 'nullable|email'
        ]);

        $patient->fill($request->all());
        $nameComplete = $patient->FirstName . ' ';
        
        if ($patient->SecondName) {
            $nameComplete .= $patient->SecondName . ' ';
        }
        $nameComplete .= $patient->Surname . ' ';
        if ($patient->SecondSurname) {
            $nameComplete .= $patient->SecondSurname;
        }
        $patient->NameCompleted = $nameComplete;
        $patient->save();

        return response()->json($patient, 200);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $patient = Patient::findOrFail($id);
        try {
            $patient->delete();
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
