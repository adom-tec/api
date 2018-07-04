<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PatientService;
use App\Patient;
use App\Professional;
use Carbon\Carbon;
use App\ServiceDetail;
use App\DetailAnswer;

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
            $professionalServices =  PatientService::select('sas.AssignService.*')
                ->join('sas.AssignServiceDetails', function ($join) use ($professional){
                    $join->on('sas.AssignServiceDetails.AssignServiceId', '=', 'sas.AssignService.AssignServiceId')
                        ->where('sas.AssignServiceDetails.ProfessionalId', $professional->ProfessionalId);
                })
                ->with(['patient', 'service', 'serviceFrecuency', 'professional', 'coPaymentFrecuency', 'state', 'entity', 'planService'])
                ->where('sas.AssignService.StateId', $request->input('status'))
                ->get()
                ->toArray();
            $count = count($professionalServices);
            $identifiers = [];
            for ($i = 0; $i < $count; $i++) {
                if (array_search($professionalServices[$i]['AssignServiceId'], $identifiers) === false) {
                    $identifiers[] = $professionalServices[$i]['AssignServiceId'];
                    $details = ServiceDetail::where('AssignServiceId', $professionalServices[$i]['AssignServiceId'])
                        ->where('ProfessionalId', $professional->ProfessionalId)->get()->toArray();
                    $professionalServices[$i]['Quantity'] = count($details);
                    $copaymentReceived = array_sum(array_column($details, 'ReceivedAmount'));
                    $professionalServices[$i]['copaymentReceived'] = $copaymentReceived;
                    $countMadeVisits = ServiceDetail::where('AssignServiceId', $professionalServices[$i]['AssignServiceId'])
                        ->where('ProfessionalId', $professional->ProfessionalId)
                        ->where('StateId', '>', 1)
                        ->count();
                    $professionalServices[$i]['countMadeVisits'] = $countMadeVisits;
                } else {
                    unset($professionalServices[$i]);
                }
            }

            return array_values($professionalServices);
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
            'date' => Carbon::createFromFormat('d-m-Y', $finalDate)->format('Y-m-d')

        ]);
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
    public function update(Request $request, $patient, $id)
    {
        $patientService = PatientService::findOrFail($id);
        $request->validate([
            'ServiceId' => 'exists:sqlsrv.cfg.Services,ServiceId',
            'Quantity' => 'numeric',
            'ServiceFrecuencyId' => 'exists:sqlsrv.cfg.ServiceFrecuency,ServiceFrecuencyId',
            'CoPaymentFrecuencyId' => 'exists:sqlsrv.cfg.CoPaymentFrecuency,CoPaymentFrecuencyId',
            'Consultation' => 'numeric',
            'External' => 'numeric',
            'EntityId' => 'exists:sqlsrv.cfg.Entities,EntityId',
            'PlanEntityId' => 'exists:sqlsrv.cfg.PlansEntity,PlanEntityId',
        ]);
        $patientService->fill($request->all());
        $patientService->load(['patient', 'service', 'serviceFrecuency', 'professional', 'coPaymentFrecuency', 'state', 'entity', 'planService']);
        $patientService->save();
        return response()->json($patientService, 200);
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

    public function storeAnswer($id, Request $request){
        $servicesDetails = ServiceDetail::where('AssignServiceId', $id)
            ->get()->pluck('AssignServiceDetailId');
        
        $request->validate([
            'answers.*.AnswerId' => 'required|exists:sqlsrv.cfg.QualityAnswers,AnswerId',
            'answers.*.QuestionId' => 'required|exists:sqlsrv.cfg.QualityQuestions,QuestionId'
        ]);
        if (count($servicesDetails)) {
            $data = [];
            $answers = $request->input('answers');
            foreach ($servicesDetails as $serviceDetail) {
                foreach ($answers as $answer) {
                    $data[] = [
                        'AnswerId' => $answer['AnswerId'],
                        'QuestionId' => $answer['QuestionId'],
                        'AssignServiceDetailId' => $serviceDetail
                    ];
                }
                ServiceDetail::where('AssignServiceDetailId', $serviceDetail)
                    ->update([
                        'QualityCallDate' => Carbon::now()->format('Y-m-d'),
                        'QualityCallUser' => $request->user()->UserId
                    ]);
            } 

            DetailAnswer::insert($data);
            return response()->json([
                'message' => 'Calificación guardada con éxito'
            ], 200);
            
        }
        return response()->json([
            'message' => 'Servicio no existe'
        ], 404);
    }
}
