<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PatientService;
use App\Patient;
use App\Professional;
use Carbon\Carbon;

class PatientServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($patient)
    {
        return PatientService::with(['patient', 'service', 'serviceFrecuency', 'professional', 'coPaymentFrecuency', 'state', 'entity', 'planService'])
            ->where('PatientId', $patient)->get();
    }

    public function getByProfessionalLogged(Request $request)
    {
        $professional = Professional::where('UserId', $request->user()->UserId)->first();
        if ($professional) {
            $request->validate([
                'status' => 'required|exists:sqlsrv.sas.StateAssignService,Id'
            ]);
            return PatientService::with(['patient', 'service', 'serviceFrecuency', 'professional', 'coPaymentFrecuency', 'state', 'entity', 'planService'])
                ->where('ProfessionalId', $professional->ProfessionalId)
                ->where('StateId', $request->input('status'))
                ->get();
        }
        return response()->json([
            'message' => 'Su usario no está configurado como profesional'
        ], 400);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $patient)
    {
        $patient = Patient::findOrFail($patient)->PatientId;
        $request->validate([
            'AuthorizationNumber' => 'required',
            'Validity' => 'required',
            'ServiceId' => 'required|exists:sqlsrv.cfg.Services,ServiceId',
            'Quantity' => 'required|numeric',
            'InitialDate' => 'required',
            'FinalDate' => 'required',
            'ServiceFrecuencyId' => 'required|exists:sqlsrv.cfg.ServiceFrecuency,ServiceFrecuencyId',
            'ProfessionalId' => 'required|exists:sqlsrv.cfg.Professionals,ProfessionalId',
            'CoPaymentAmount' => 'required',
            'CoPaymentFrecuencyId' => 'required|exists:sqlsrv.cfg.CoPaymentFrecuency,CoPaymentFrecuencyId',
            'Consultation' => 'required|numeric',
            'External' => 'required|numeric',
            'ContractNumber' => 'required',
            'EntityId' => 'required|exists:sqlsrv.cfg.Entities,EntityId',
            'PlanEntityId' => 'required|exists:sqlsrv.cfg.PlansEntity,PlanEntityId',
            'Cie10' => 'required',
            'ApplicantName' => 'required'
        ]);
        extract($request->all());
        $Observation = $request->input('Observation') ? $request->input('Observation') : '';
        $DescriptionCie10 = $request->input('DescriptionCie10') ? $request->input('DescriptionCie10') : '';
        $AssignedBy = $request->user()->UserId; 
        $Validity = Carbon::createFromFormat('Y-m-d', $Validity)->format('Y-d-m');
        $InitialDate = Carbon::createFromFormat('Y-m-d', $InitialDate)->format('Y-d-m');
        $FinalDate = Carbon::createFromFormat('Y-m-d', $FinalDate)->format('Y-d-m');
        $sql = "exec sas.CreateAssignServiceAndDetails_editado ${patient}, '${AuthorizationNumber}', '${Validity}', '${ApplicantName}', ${ServiceId}, ${Quantity}, '${InitialDate}', '${FinalDate}', ${ServiceFrecuencyId}, ${ProfessionalId}, ${CoPaymentAmount}, ${CoPaymentFrecuencyId}, ${Consultation}, ${External}, 1, '${Observation}', '${ContractNumber}', '${Cie10}', '${DescriptionCie10}', ${PlanEntityId}, ${EntityId}, ${AssignedBy}";
        /*$patientService = new PatientService($request->all());
        $patientService->PatientId = $patient;
        $patientService->StateId = 1;
        $patientService->save();*/
        $patientService = \DB::select(\DB::raw($sql))[0]->AssignServiceId;
        $patientService = PatientService::with(['patient', 'service', 'serviceFrecuency', 'professional', 'coPaymentFrecuency', 'state', 'entity', 'planService'])
            ->findOrFail($patientService);
        //enviar correo
        return response()->json($patientService, 201);
    }

    public function getFinalDate(Request $request)
    {
        $request->validate([
            'Quantity' => 'required|numeric',
            'ServiceFrecuencyId' => 'required|exists:sqlsrv.cfg.ServiceFrecuency,ServiceFrecuencyId',
            'InitialDate' => 'required'
        ]);
        $quantity = $request->input('Quantity');
        $serviceFrecuencyId = $request->input('ServiceFrecuencyId');
        $initialDate = $request->input('InitialDate');
        $sql = "exec sas.CalculateFinalDateAssignService_editado $quantity,$serviceFrecuencyId,'$initialDate'";
        $finalDate = \DB::select(\DB::raw($sql))[0]->FinalDateAssignService;
        return response()->json([
            'date' => Carbon::createFronFormat('Y-d-m', $finalDate)->format('Y-m-d')
        ]);;
    }
    

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
