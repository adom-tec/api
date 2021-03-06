<?php

namespace App\Http\Controllers;

use App\ExcelBuilder;
use App\Mail\ChangedInitialDate;
use App\Mail\ServiceAssigned;
use App\ReasonChangeInitDate;
use App\ServiceObservation;
use Illuminate\Http\Request;
use App\PatientService;
use App\Patient;
use App\Professional;
use Carbon\Carbon;
use App\ServiceDetail;
use App\DetailAnswer;
use Validator;
use App\Service;

class PatientServiceController extends Controller
{

    public function __construct()
    {
        $this->middleware('verify.action:/AssignService/Get')->only('index');
        $this->middleware('verify.action:/AssignService/Create')->only('store');
        $this->middleware('verify.action:/AssignService/Edit')->only('update');
    }

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
            'ProfessionalId' => 'required',
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
        $sql = "exec sas.CreateAssignServiceAndDetails ${patient}, '${AuthorizationNumber}', '${Validity}', '${ApplicantName}', ${ServiceId}, ${Quantity}, '${InitialDate}', '${FinalDate}', ${ServiceFrecuencyId}, ${ProfessionalId}, ${CoPaymentAmount}, ${CoPaymentFrecuencyId}, ${Consultation}, ${External}, 1, '${Observation}', '${ContractNumber}', '${Cie10}', '${DescriptionCie10}', ${PlanEntityId}, ${EntityId}, ${AssignedBy}";
        $patientService = \DB::select(\DB::raw($sql))[0]->AssignServiceId;

        $updates = [
            'AuthorizationNumber' => $AuthorizationNumber
        ];

//        if ($request->input('InitDateAuthorizationNumber')) {
//            $updates['InitDateAuthorizationNumber'] = $request->input('InitDateAuthorizationNumber');
//        }
//
//        if ($request->input('FinalDateAuthorizationNumber')) {
//            $updates['FinalDateAuthorizationNumber'] = $request->input('FinalDateAuthorizationNumber');
//        }
        ServiceDetail::where('AssignServiceId', $patientService)
            ->update($updates);

        $patientService = PatientService::with(['patient', 'service', 'serviceFrecuency', 'professional', 'coPaymentFrecuency', 'state', 'entity', 'planService'])
            ->findOrFail($patientService);

        $change = false;

        if ($request->input('InitDateAuthorizationNumber')) {
            $patientService->InitDateAuthorizationNumber = $request->input('InitDateAuthorizationNumber');
            $change = true;
        }

        if ($request->input('FinalDateAuthorizationNumber')) {
            $patientService->FinalDateAuthorizationNumber = $request->input('FinalDateAuthorizationNumber');
            $change = true;
        }

        if ($change) {
            $patientService->save();
        }

	    $service = Service::findOrFail($ServiceId);
        if ($ProfessionalId != -1 && $service->ServiceTypeId != 3) {
            $professional = Professional::findOrFail($ProfessionalId);
            $name = $professional->user->FirstName . ' ';
            if ($professional->user->SecondName) {
                $name .= $professional->user->SecondName . ' ';
            }
            $name .= $professional->user->Surname . ' ';
            if ($professional->user->SecondSurname) {
                $name .= $professional->user->SecondSurname;
            }
	    
	    \Mail::to($professional->user->Email)->send(new ServiceAssigned($name, $patientService));

        }
        
        return response()->json($patientService, 201);
    }

    public function getFinalDate(Request $request)
    {
	$validator = Validator::make($request->all(), [
            'Quantity' => 'required|numeric',
            'ServiceFrecuencyId' => 'required|exists:sqlsrv.cfg.ServiceFrecuency,ServiceFrecuencyId',
            'InitialDate' => 'required'
        ]);
	
	if (!$validator->fails()) {
	    $quantity = $request->input('Quantity');
	    $serviceFrecuencyId = $request->input('ServiceFrecuencyId');
	    $initialDate = $request->input('InitialDate');
	    $sql = "exec sas.CalculateFinalDateAssignService $quantity,$serviceFrecuencyId,'$initialDate'";
	    $finalDate = \DB::select(\DB::raw($sql))[0]->FinalDateAssignService;
	    return response()->json([
	        'date' => Carbon::createFromFormat('d-m-Y', $finalDate)->format('Y-m-d')
	    ]);
	} else {
	    return response()->json([
		'message' => 'Valores incorrectos, por favor verifique e intente nuevamente.'
            ], 422);
	}
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
        if ($request->input('InitialDate') && $request->input('InitialDate') != $patientService->InitialDate) {
            $request->validate([
                'FinalDate' => 'required',
                'ReasonChangeInitDateId' => 'required|exists:sqlsrv.sas.ReasonChangeInitDate,id'
            ]);
            $reasonChangeInitDate = ReasonChangeInitDate::findOrFail($request->input('ReasonChangeInitDateId'));
            $countCompletedVisitis = ServiceDetail::where('AssignServiceId', $id)
                ->where('StateId', 2)
                ->count();
            if ($countCompletedVisitis) {
                return response()->json([
                    'message' => 'Error, No se puede cambiar la fecha de inicio porque ya hay visitas realizadas'
                ], 400);
            }

        }
        $patientService->fill($request->except(['AuthorizationNumber', 'ReasonChangeInitDateId']));
        $patientService->load(['patient', 'service', 'serviceFrecuency', 'professional', 'coPaymentFrecuency', 'state', 'entity', 'planService']);
        $patientService->save();
        if (isset($reasonChangeInitDate)) {
            $serviceObservation = new ServiceObservation();
            $serviceObservation->UserId = $request->user()->UserId;
            $serviceObservation->AssignServiceId = $id;
            $serviceObservation->Description = 'Fecha de inicio del servicio modificada por razón: ' . $reasonChangeInitDate->name;
            $serviceObservation->save();

            if ($patientService->ProfessionalId != -1) {
                $professional = Professional::findOrFail($patientService->ProfessionalId);
                $name = $professional->user->FirstName . ' ';
                if ($professional->user->SecondName) {
                    $name .= $professional->user->SecondName . ' ';
                }
                $name .= $professional->user->Surname . ' ';
                if ($professional->user->SecondSurname) {
                    $name .= $professional->user->SecondSurname;
                }
                $service = PatientService::findOrFail($id);
                \Mail::to($professional->user->Email)->send(new ChangedInitialDate($name, $service));

            }
        }

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

    public function getChartData($serviceType)
    {
        $sql = "exec sas.GetChartData $serviceType";
        $data = \DB::select($sql);
        return $data;
    }

    public function getIrregularServices()
    {
        $irregularServices = \DB::select("exec sas.GetIrregularServices");
		$irregularServices = json_decode(json_encode($irregularServices), true);
		for ($i = 0; $i < count($irregularServices); $i++) {
			unset($irregularServices[$i]['Document'], $irregularServices[$i]['DateLastVisit']);
		}
		return $irregularServices;
    }

    public function getSuspendedServices()
    {
        $data = [];
        $services = PatientService::select('AssignServiceId', 'PatientId', 'ServiceId')
            ->where('StateId', 4)
            ->with(['patient', 'service'])
            ->get();
        foreach ($services as $service) {

            $data[] = [
                'AssignServiceId' => $service->AssignServiceId,
                'PatientId' => $service->PatientId,
                'PatientName' => $service->patient->NameCompleted,
                'ServiceName' => $service->service->Name
            ];
        }

        return $data;

    }

    public function getIrregularServicesXLS()
    {
        $data = \DB::select("exec sas.GetIrregularServices");
        $data =json_decode(json_encode($data), true);
        if ($data) {
            $rows = [];
            $header = [
                'Paciente',
				'Documento del Paciente',
                'Servicio',
                'Motivo',
				'Última Fecha de Visita'
            ];

            foreach ($data as $datum) {
                $rows[] = [
                  $datum['PatientName'],
				  $datum['Document'],
                  $datum['ServiceName'],
                  $datum['Reason'],
				  $datum['DateLastVisit']
                ];
            }
            $excel = new ExcelBuilder($header, $rows);
            $excel->build();
            return $excel->get();
        }

    }

    public function getServicesWithoutProfessional()
    {
        
        $services = PatientService::select(\DB::raw('DISTINCT(sas.AssignService.AssignServiceId), PatientId, ServiceId'))
            ->join('sas.AssignServiceDetails', function ($join) {
                $join->on('sas.AssignServiceDetails.AssignServiceId', '=', 'sas.AssignService.AssignServiceId')
                    ->where('sas.AssignServiceDetails.ProfessionalId', '-1')
                    ->where('sas.AssignServiceDetails.StateId', '<>', 3);
            })
            ->where('sas.AssignService.StateId', '<>', 3)
            ->with(['patient:PatientId,NameCompleted', 'service:ServiceId,Name'])
            ->get();
        return $services;
    }

    public function getProfessionalsCopayment()
    {
        $data = ServiceDetail::select(\DB::raw('sas.AssignServiceDetails.ProfessionalId, sum(sas.AssignServiceDetails.ReceivedAmount) as ReceivedAmount'))
            ->join('sas.AssignService', function ($join) {
                $join->on('sas.AssignService.AssignServiceId', '=', 'sas.AssignServiceDetails.AssignServiceId')
                    ->where('CopaymentStatus', 0);
            })
            ->where('PaymentType', 1)
            ->where('sas.AssignServiceDetails.ProfessionalId', '<>',-1)
            ->where('ReceivedAmount', '>', '200000')
            ->groupBy('sas.AssignServiceDetails.ProfessionalId')
            ->get();
        $data->load('professional.user');
        return $data;
    }
}
