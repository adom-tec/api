<?php

namespace App\Http\Controllers;

use App\Patient;
use App\Professional;
use App\ServiceDetail;
use App\User;
use App\WorkScheduleRange;
use Illuminate\Http\Request;
use App\ExcelBuilder;
use App\DetailAnswer;
use App\CollectionAccount;
use App\Http\Controllers\CopaymentController;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('verify.action:/SpecialReport/Create')->only(['getConsolidadoReport', 'getDetalleReport']);
        $this->middleware('verify.action:/CopaymentReport/Create')->only(['getCopaymentReport', 'getNominaReport']);
	$this->middleware('verify.action:/prosessionalreport/Create')->only('getProfessionalReport');
    }

    public function getConsolidadoReport(Request $request)
    {
        $sql = "SELECT	Ags.AssignServiceId
                    ,(ISNULL(pat.FirstName,'') + ' ' + ISNULL(pat.SecondName, '') + ' ' + ISNULL(pat.Surname, '') + ' ' + ISNULL(pat.SecondSurname, '')) AS PatientName
		            ,doc.Name PatientDocumentType
		            ,pat.Document PatientDocument
		            ,ent.Name EntityName
		            ,pe.Name PlanEntityName
		            ,Ags.AuthorizationNumber
                    ,Ags.ContractNumber
                    ,Ser.Name as ServiceName
                    ,Ags.Quantity TotalSessions
		            ,(Ags.Quantity - (select count(det.AssignServiceDetailId) from sas.AssignServiceDetails det WHERE det.AssignServiceId = Ags.AssignServiceId AND det.StateId = 3)) AS ProgrammedSessions
                    ,(select count(det.AssignServiceDetailId) from sas.AssignServiceDetails det WHERE det.AssignServiceId = Ags.AssignServiceId AND det.StateId = 2) as CompletedSessions
		            ,sef.Name as ServiceFrecuency
		            ,sta.Name AS ServiceStatus
		            ,pr.Rate
		            ,Ags.CoPaymentAmount
		            ,cpf.Name CopaymentFrecuency
		            ,CASE Ags.CopaymentStatus WHEN 0 THEN 'SIN ENTREGAR' ELSE 'ENTREGADO' END CopaymentStatus
		            ,FORMAT(Ags.RecordDate, 'dd-MM-yyyy HH:mm:ss') RequestDate
                    ,FORMAT(Ags.InitialDate, 'dd-MM-yyyy') InitialDate
                    ,FORMAT(Ags.FinalDate, 'dd-MM-yyyy') FinalDate
                    ,FORMAT((SELECT MIN(DateVisit) FROM sas.AssignServiceDetails where AssignServiceId = Ags.AssignServiceId), 'dd-MM-yyyy') RealInitialDate
					,FORMAT((SELECT DateVisit FROM	sas.AssignServiceDetails WHERE StateId = 2 AND AssignServiceDetailId 
							IN( SELECT	MAX(AssignServiceDetailId) 
								FROM	sas.AssignServiceDetails 
								WHERE	AssignServiceId = Ags.AssignServiceId)),'dd-MM-yyyy') RealFinalDate		
		            ,Ags.Cie10	
		            ,Ags.DescriptionCie10
		            ,pt.Name PatientType			  
		            ,CONCAT(pat.Age, ' ', ut.Name) Age
		            ,pat.BirthDate PatientBirthday
		            ,gen.Name PatientGender 
		            ,pat.Telephone1 PatientPhone1
		            ,pat.Telephone2 PatientPhone2
		            ,pat.Address PatientAddress
		            ,pat.Email PatientEmail
                    ,(ISNULL(usr.FirstName,'') + ' ' + ISNULL(usr.SecondName, '') + ' ' + ISNULL(usr.Surname, '') + ' ' + ISNULL(usr.SecondSurname, '')) AS AssignedBy
                    ,SUBSTRING((SELECT ',' + Description AS 'data()' FROM sas.AssignServiceObservation  
                                WHERE AssignServiceId = Ags.AssignServiceId 
							    FOR XML PATH('')),2,9999) AS Observations							
            FROM	    sas.AssignService Ags
            INNER JOIN cfg.Patients pat ON Ags.PatientId = pat.PatientId
            INNER JOIN cfg.UnitTime ut ON ut.Id= pat.UnitTimeId
            INNER JOIN Cfg.PatientType pt ON pat.PatientTypeId = pt.Id
            INNER JOIN cfg.Gender gen ON pat.GenderId = gen.Id
            INNER JOIN cfg.DocumentType doc ON doc.Id = pat.DocumentTypeId
            INNER JOIN cfg.Services Ser ON Ser.ServiceId = Ags.ServiceId
            INNER JOIN cfg.ServiceFrecuency sef ON sef.ServiceFrecuencyId = Ags.ServiceFrecuencyId
            INNER JOIN cfg.CoPaymentFrecuency cpf ON cpf.CoPaymentFrecuencyId = Ags.CoPaymentFrecuencyId
            INNER JOIN sas.StateAssignService sta ON sta.Id = Ags.StateId
            INNER JOIN cfg.Entities ent ON ent.EntityId = Ags.EntityId
            INNER JOIN cfg.PlansEntity pe ON pe.PlanEntityId = Ags.PlanEntityId
            INNER JOIN cfg.PlansRates pr ON pr.PlanEntityId = Ags.PlanEntityId AND pr.ServiceId = Ags.ServiceId
            INNER JOIN sec.Users usr ON usr.UserId = Ags.AssignedBy
            WHERE 1 = 1";

        if ($request->input('InitDate')) {
            $initDate = $request->input('InitDate');
            $sql .= " AND (SELECT MIN(DateVisit) FROM sas.AssignServiceDetails where AssignServiceId = Ags.AssignServiceId and sas.AssignServiceDetails.StateId = 2) >= CONVERT(DATE, '$initDate', 105)";
        }

        if ($request->input('FinalDate')) {
            $finalDate = $request->input('FinalDate');
            $sql .= " AND (SELECT MAX(DateVisit) FROM sas.AssignServiceDetails WHERE AssignServiceId = Ags.AssignServiceId and sas.AssignServiceDetails.StateId = 2) <= CONVERT(DATE, '$finalDate', 105)";
        }

        if ($request->input('EntityId')) {
            $sql .= " AND Ags.EntityId = " . $request->input('EntityId');
        }

        if ($request->input('PatientType')) {
            $sql .= " AND pt.Id = " . $request->input('PatientType');
        }

        if ($request->input('ServiceId') && !$request->input('ServiceType')) {
            $sql .= " AND Ags.ServiceId = " . $request->input('ServiceId');
        } else if ($request->input('ServiceType')) {
            $sql .= " AND Ser.ServiceTypeId = " . $request->input('ServiceType');
        }

        $sql .= " ORDER BY RealInitialDate DESC";

        $data = json_decode(json_encode(\DB::select(\DB::raw($sql))), true);

        $maxCountProfessionals = 0;

        $i = 0;
        foreach ($data as $datum) {
            $serviceId = $datum['AssignServiceId'];
            $professionals = Professional::select(\DB::raw('DISTINCT(cfg.Professionals.ProfessionalId), Document, UserId'))
                ->join('sas.AssignServiceDetails', function ($join) use ($serviceId) {
                   $join->on('cfg.Professionals.ProfessionalId', '=', 'sas.AssignServiceDetails.ProfessionalId')
                        ->where('sas.AssignServiceDetails.AssignServiceId', $serviceId);
                })->with('user')
                ->get()->toArray();

                $j = 0;
                foreach ($professionals as $professional) {
                    $name = $professional['user']['FirstName'] . ' ';
                    if ($professional['user']['SecondName']) {
                        $name .= $professional['user']['SecondName'] . ' ';
                    }
                    $name .= $professional['user']['Surname'] . ' ';
                    if ($professional['user']['SecondSurname']) {
                        $name .= $professional['user']['SecondSurname'];
                    }
                    $data[$i]['DOC' . $j] = $professional['Document'];
                    $data[$i]['NOMBRE' . $j] = $name;
                    $j++;
                }
            unset($data[$i]['AssignServiceId']);
            $maxCountProfessionals = count($professionals) > $maxCountProfessionals ? count($professionals) : $maxCountProfessionals;
            $i++;
        }

        $header = [
            'NOMBRE PACIENTE',
            'TIPO DOCUMENTO',
            'NÚMERO DOCUMENTO',
            'ENTIDAD',
            'PLAN',
            'CONTRATO',
            'AUTORIZACIÓN',
            'SERVICIO',
            'N. TOTAL SESIONES',
            'N. SESIONES COMPL',
            'N. SESIONES PROGRAM',
            'FREC. SERVICIO',
            'ESTADO',
            'TARIFA',
            'COPAGO',
            'FREC. COPAGO',
            'ESTADO COPAGO',
            'FECHA INICIO SOLICITUD',
            'FECHA INICIO TENT.',
            'FECHA FIN TENT.',
            'FECHA INICIO REAL',
            'FECHA FIN REAL',
            'CIE10',
            'DES. CIE10',
            'TIPO PACIENTE',
            'EDAD',
            'FECHA NACIMIENTO',
            'GENERO',
            'TELÉFONO 1',
            'TELÉFONO 2',
            'DIRECCIÓN',
            'EMAIL',
            'ASIGNADO POR',
            'OBSERVACIONES'
        ];
        for ($i = 1; $i <= $maxCountProfessionals; $i++) {
            $header[] = 'DOC' . $i;
            $header[] = 'NOMBRE' . $i;
        }
        
        $excel = new ExcelBuilder($header, $data);
        $excel->build();

        return response($excel->get())->header('Access-Control-Allow-Origin','*');
    }

    public function getDetalleReport(Request $request)
    {
        $sql = "SELECT	  AssignServiceDetailId
        ,(ISNULL(pat.FirstName,'') + ' ' + ISNULL(pat.SecondName, '') + ' ' + ISNULL(pat.Surname, '') + ' ' + ISNULL(pat.SecondSurname, '')) AS PatientName
        ,doc.Name PatientDocumentType
        ,pat.Document PatientDocument
        ,ent.Name EntityName
        ,pl.Name PlanName
        ,Ags.AuthorizationNumber
        ,Ags.ContractNumber
        ,Ser.Name ServiceName
        ,Sef.Name ServiceFrecuency
        ,Ags.Cie10
        ,Sta.Name VisitStatus
        ,FORMAT(Ags.RecordDate, 'dd-MM-yyyy HH:mm:ss') RequestDate
        ,FORMAT(Asd.DateVisit, 'dd-MM-yyyy') DateVisit
        ,cpf.Name CopaymentFrecuency
        ,Asd.ReceivedAmount
        ,Asd.Pin
        ,Asd.OtherAmount
        ,FORMAT(Asd.RecordDate, 'dd-MM-yyyy HH:mm:ss') RecordDate
        ,(ISNULL(usr.FirstName,'') + ' ' + ISNULL(usr.SecondName, '') + ' ' + ISNULL(usr.Surname, '') + ' ' + ISNULL(usr.SecondSurname, '')) AS ProfessionalName
        ,Pro.Document ProfessionalDocument
        ,Ser.HoursToInvest
        ,CASE Asd.Verified WHEN 1 THEN 'SI' ELSE 'NO' END Verified 
        ,(ISNULL(cal.FirstName,'') + ' ' + ISNULL(cal.SecondName, '') + ' ' + ISNULL(cal.Surname, '') + ' ' + ISNULL(cal.SecondSurname, '')) AS VerifiedBy 
        ,CASE WHEN Asd.VerificationDate IS NULL THEN '' ELSE FORMAT(Asd.VerificationDate, 'dd-MM-yyyy HH:mm:ss') END AS VerificationDate
        ,CASE WHEN Asd.QualityCallDate IS NULL THEN '' ELSE FORMAT(Asd.QualityCallDate, 'dd-MM-yyyy HH:mm:ss') END AS QualityCallDate
        ,(ISNULL(qua.FirstName,'') + ' ' + ISNULL(qua.SecondName, '') + ' ' + ISNULL(qua.Surname, '') + ' ' + ISNULL(qua.SecondSurname, '')) AS QualityCallUser 
        ,Asd.Observation
        FROM sas.AssignServiceDetails Asd
        INNER JOIN sas.AssignService Ags ON Asd.AssignServiceId = Ags.AssignServiceId            
        INNER JOIN cfg.Services Ser ON Ser.ServiceId = Ags.ServiceId
        INNER JOIN cfg.Entities ent ON ent.EntityId = Ags.EntityId
        INNER JOIN cfg.PlansEntity pl ON pl.PlanEntityId =  Ags.PlanEntityId
        INNER JOIN cfg.ServiceFrecuency sef ON sef.ServiceFrecuencyId = Ags.ServiceFrecuencyId
        INNER JOIN cfg.CoPaymentFrecuency cpf ON cpf.CoPaymentFrecuencyId = Ags.CoPaymentFrecuencyId
        INNER JOIN sas.StateAssignService sta ON sta.Id = Asd.StateId
        INNER JOIN cfg.PlansEntity pe ON pe.PlanEntityId = Ags.PlanEntityId
        INNER JOIN cfg.Patients pat ON Ags.PatientId = pat.PatientId
        INNER JOIN Cfg.PatientType pt ON pat.PatientTypeId = pt.Id
        INNER JOIN cfg.DocumentType doc ON doc.Id = pat.DocumentTypeId
        LEFT JOIN cfg.Professionals Pro ON Pro.ProfessionalId = Asd.ProfessionalId
        LEFT JOIN sec.Users usr ON usr.UserId = Pro.UserId
        LEFT JOIN sec.Users cal ON cal.UserId = Asd.VerifiedBy
        LEFT JOIN sec.Users qua ON qua.UserId = Asd.QualityCallUser
        WHERE Asd.StateId = 2";

        if ($request->input('InitDate')) {
            $initDate = $request->input('InitDate');
            $sql .= " AND Asd.DateVisit >= CONVERT(DATE, '$initDate', 105)";
        }

        if ($request->input('FinalDate')) {
            $finalDate = $request->input('FinalDate');
            $sql .= " AND Asd.DateVisit <= CONVERT(DATE, '$finalDate', 105)";
        }

        if ($request->input('EntityId')) {
            $sql .= " AND Ags.EntityId = " . $request->input('EntityId');
        }

        if ($request->input('PatientType')) {
            $sql .= " AND pt.Id = " . $request->input('PatientType');
        }

        if ($request->input('ServiceId') && !$request->input('ServiceType')) {
            $sql .= " AND Ags.ServiceId = " . $request->input('ServiceId');
        } else if ($request->input('ServiceType')) {
            $sql .= " AND Ser.ServiceTypeId = " . $request->input('ServiceType');
        }

        $sql .= " ORDER BY Asd.DateVisit DESC";

        $data = json_decode(json_encode(\DB::select(\DB::raw($sql))), true);
     
        $i = 0;
        
        $answerId = [];
        foreach ($data as $datum) {
            $serviceDetail = $datum['AssignServiceDetailId'];
            $answers = DetailAnswer::where('AssignServiceDetailId', $serviceDetail)
                ->orderBy('QuestionId', 'asc')
                ->get();
            
            $j = 0;
            foreach ($answers as $answer) {
                if (!in_array($answer->QuestionId, $answerId)) {
                    $answerId[] = $answer->QuestionId;
                }

                $data[$i]['PREGUNTA' . $j] = $answer->AnswerId; 
                $j++;
            }
            unset($data[$i]['AssignServiceDetailId']);
            $i++;
        }

        $header = [
            'NOMBRE PACIENTE',
            'TIPO DOCUMENTO',
            'NÚMERO DOCUMENTO',
            'ENTIDAD',
            'PLAN',
            'AUTORIZACIÓN',
            'NÚMERO CONTRATO',
            'SERVICIO',
            'FREC. SERVICIO',
            'CIE 10',
            'ESTADO',
            'FECHA SOLICITUD',
            'FECHA DE ATENCIÓN',
            'FRECUENCIA DEL COPAGO',
            'COPAGO RECIBIDO',
            'PIN',
            'OTROS VAL. RECIBIDOS',
            'FECHA DE REGISTRO',
            'PROFESIONAL',
            'DOC. PROF.',
            'HORAS INVERTIDAS',
            'VERIFICADO',
            'VERIFICADO POR',
            'FECHA VERIFICADO',
            'FECHA LLAM. CALIDAD',
            'USUARIO CALIDAD',
            'COMENTARIOS'
        ];

        foreach ($answerId as $id) {
            $header[] = 'PREGUNTA' . $id;
        }


        $excel = new ExcelBuilder($header, $data);
        $excel->build();

        return $excel->get();

    }

    public function getPaymentReport(Request $request)
    {
        $sql = "SELECT	Asd.ProfessionalId
            ,(ISNULL(usr.FirstName,'') + ' ' + ISNULL(usr.SecondName, '') + ' ' + ISNULL(usr.Surname, '') + ' ' + ISNULL(usr.SecondSurname, '')) AS ProfessionalName
            ,Pro.Document ProfessionalDocument
            ,UPPER(Typ.Description) ServiceType
            ,Ser.Name ServiceName
            ,FORMAT(Asd.DateVisit, 'dd-MM-yyyy HH:mm:ss') InitialDate
            ,UPPER(CASE DATEPART(dw,CONVERT(DATETIME,Asd.DateVisit,120)) 
                WHEN 2 THEN 'LUNES' 
                WHEN 3 THEN 'MARTES' 
                WHEN 4 THEN 'MIERCOLES' 
                WHEN 5 THEN 'JUEVES' 
                WHEN 6 THEN 'VIERNES' 
                WHEN 7 THEN 'SABADO' 
                WHEN 1 THEN 'DOMINGO' 
                ELSE '' END ) DayOfWeek
            ,'NO' IsHoliday
            ,ser.Value AS Rate
            ,CASE PaymentType WHEN 1 THEN 'EFECTIVO' WHEN 2 THEN 'PIN' WHEN 3 THEN 'OTRO' ELSE NULL END PaymentType
            ,asd.Pin
            ,asd.ReceivedAmount
            ,asd.OtherAmount		                
            ,(ISNULL(pat.FirstName,'') + ' ' + ISNULL(pat.SecondName, '') + ' ' + ISNULL(pat.SecondName, '') + ' ' + ISNULL(pat.SecondSurname, '')) AS PatientName
            ,doc.Name PatientDocumentType
            ,pat.Document PatientDocument
            ,ent.Name EntityName
            ,pl.Name PlanName
            ,FORMAT(Ags.RecordDate,'dd-MM-yyyy HH:mm:ss') RequestDate
            ,Ser.HoursToInvest
            ,CASE Asd.Verified WHEN 1 THEN 'SI' ELSE 'NO' END Verified		                
            ,(ISNULL(cal.FirstName,'') + ' ' + ISNULL(cal.SecondName, '') + ' ' + ISNULL(cal.Surname, '') + ' ' + ISNULL(cal.SecondSurname, '')) AS VerifiedBy  
            ,CASE WHEN Asd.VerificationDate IS NULL THEN '' ELSE CONVERT(VARCHAR(30), Asd.VerificationDate, 121) END AS VerificationDate		                
        FROM sas.AssignServiceDetails Asd
        INNER JOIN sas.AssignService Ags ON Asd.AssignServiceId = Ags.AssignServiceId
        LEFT JOIN cfg.Professionals Pro ON Pro.ProfessionalId = Asd.ProfessionalId
        LEFT JOIN sec.Users usr ON usr.UserId = Pro.UserId
        LEFT JOIN sec.Users cal ON cal.UserId = Asd.VerifiedBy
        INNER JOIN cfg.Services Ser ON Ser.ServiceId = Ags.ServiceId
        INNER JOIN cfg.Entities ent ON ent.EntityId = Ags.EntityId
        INNER JOIN cfg.PlansEntity pl ON pl.PlanEntityId =  Ags.PlanEntityId
        INNER JOIN cfg.PlansRates pr ON pr.PlanEntityId = pl.PlanEntityId AND pr.ServiceId = Ags.ServiceId 
        INNER JOIN cfg.ServiceType Typ ON Ser.ServiceTypeId = Typ.Id
        INNER JOIN cfg.ServiceFrecuency sef ON sef.ServiceFrecuencyId = Ags.ServiceFrecuencyId
        INNER JOIN cfg.CoPaymentFrecuency cpf ON cpf.CoPaymentFrecuencyId = Ags.CoPaymentFrecuencyId
        INNER JOIN sas.StateAssignService sta ON sta.Id = Ags.StateId
        INNER JOIN cfg.PlansEntity pe ON pe.PlanEntityId = Ags.PlanEntityId
        INNER JOIN cfg.Patients pat ON Ags.PatientId = pat.PatientId
        INNER JOIN cfg.DocumentType doc ON doc.Id = pat.DocumentTypeId
        WHERE Asd.StateId = 2 AND Asd.ProfessionalId NOT IN (-1, 0) ";

        if ($request->input('InitDate')) {
            $initDate = $request->input('InitDate');
            $sql .= " AND Asd.DateVisit >= CONVERT(DATETIME, '$initDate', 105) ";
        }

        if ($request->input('FinalDate')) {
            $finalDate = $request->input('FinalDate');
            $sql .= " AND Asd.DateVisit <= CONVERT(DATETIME, '$finalDate', 105) ";
        }

        if ($request->input('ServiceId')) {
            $service = $request->input('ServiceId');
            $sql .= " AND Ags.ServiceId = " . $service;
        }

        if ($request->input('EntityId')) {
            $entity = $request->input('EntityId');
            $sql .= " AND Ags.EntityId = " . $entity;
        }

        if ($request->input('PlanEntityId')) {
            $planEntity = $request->input('PlanEntityId');
            $sql .= " AND Ags.PlanEntityId = " . $planEntity;
        }



        $sql .= " ORDER BY Asd.DateVisit DESC";

        $data = json_decode(json_encode(\DB::select(\DB::raw($sql))), true);

        for ($i = 0; $i < count($data); $i++) {
            unset($data[$i]['ProfessionalId']);
        } 

        $header = [
            'NOMBRE PROFESIONAL',
            'NÚMERO DOCUMENTO',
            'DEPARTAMENTO',
            'SERVICIO',
            'FECHA ATENCIÓN',
            'DÍA',
            'FESTIVO',
            'HONORARIOS',
            'TIPO COPAGO',
            'PIN',
            'EFECTIVO REPORTADO',
            'OTROS REPORTADOS',
            'NOMBRE PACIENTE',
            'TIPO DOCUMENTO PACIENTE',
            'No. DOCUMENTO PACIENTE',
            'ENTIDAD',
            'PLAN',
            'FECHA SOLICITUD',
            'HORAS INVERTIDAS',
            'VERIFICADO',
            'VERIFICADO POR',
            'FECHA VERIFICADO'	
        ];

        $excel = new ExcelBuilder($header, $data);
        $excel->build();
        return $excel->get();
    }

    public function getCopaymentReport(Request $request)
    {
        $initDate = Carbon::createFromFormat('d-m-Y', $request->input('InitDate'))->format('Y-m-d');
        $finalDate = Carbon::createFromFormat('d-m-Y', $request->input('FinalDate'))->format('Y-m-d');
        $professional = $request->input('ProfessionalId');
        $colecctionAccounts = CollectionAccount::select(\DB::raw('DISTINCT(sas.AssignServiceDetails.AssignServiceDetailId), sas.CollectionAccounts.id, ProfessionalTakenAmount'))
            ->join('sas.VisitCollectionAcount', 'sas.VisitCollectionAcount.CollectionAccountId', '=', 'sas.CollectionAccounts.id')
            ->join('sas.AssignServiceDetails', function($join) use ($professional){
                $join = $join->on('sas.AssignServiceDetails.AssignServiceDetailId', '=', 'sas.VisitCollectionAcount.AssignServiceDetailId');
                if ($professional) {
                    $join->where('sas.AssignServiceDetails.ProfessionalId', $professional);
                }
            })
            ->whereBetween('sas.CollectionAccounts.RecordDate', [$initDate, $finalDate])
            ->get()->toArray();

        $detailsId = array_column($colecctionAccounts, 'AssignServiceDetailId');
        $data = new CopaymentController();
        $data = $data->getServices('', '', '', 1, 3, [],$detailsId);

        $collectionAccountId = [];
        $cant = count($colecctionAccounts);
        for ($i = 0; $i < $cant; $i++ ) {
            if (!in_array($colecctionAccounts[$i]['id'], $collectionAccountId) ) {
                $collectionAccountId[] = $colecctionAccounts[$i]['id'];
            } else {
                unset($colecctionAccounts[$i]);
            }
        }
        $header = [
            'NÚMERO  DE IDENTIFICACIÓN DEL PROFESIONAL',
            'NOMBRE PROFESIONAL',
            'TIPO DE DOCUMENTO DEL PACIENTE',
            'N° DOCUMENTO  DEL  PACIENTE',
            'NOMBRE COMPLETO DEL PACIENTE',
            'ENTIDAD',
            'AUTORIZACIÓN',
            'TIPO DE TERAPIA',
            'VALOR A PAGAR AL PROFESIONAL POR TERAPIA',
            'CANTIDAD DE TERAPIAS REALIZADAS',
            'COPAGO',
            'FRECUENCIA COPAGO',
            'VALOR TOTAL RECAUDO COPAGOS',
            'VALE/PIN',
            'KIT MNB',
            'CUANTOS KIT UTILIZO',
            'OTROS VALORES RECIBIDOS',
            'VALOR A ENTREGAR',
            'SUBTOTAL'
        ];

        $whiteRow = [];
        for ($i = 0; $i <17; $i++ ) {
            $whiteRow[] = ' ';
        }

        

        $totalCopaymentReceived = array_sum(array_column($data, 'TotalCopaymentReceived'));
        $subTotal = array_sum(array_column($data, 'SubTotal'));
        $professionalTakenAmount = array_sum(array_column($colecctionAccounts, 'ProfessionalTakenAmount'));
        $totalOthersValue = array_sum(array_column($data, 'OtherValuesReceived'));
        $subTotal = $subTotal - $professionalTakenAmount;

        $rowTotalCopayment = $whiteRow;
        $rowTotalCopayment[] = $totalCopaymentReceived;
        $rowTotalCopayment[16] = 'TOTAL COPAGOS';

        $rowprofessionalTakenAmount = $whiteRow;
        $rowprofessionalTakenAmount[] = $professionalTakenAmount;
        $rowprofessionalTakenAmount[16] = 'TOTAL MONTO CONSERVADO POR EL PROFESIONAL';

        $rowSubTotal = $whiteRow;
        $rowSubTotal[] = $subTotal;
        $rowSubTotal[16] = 'TOTAL A PAGAR AL PROFESIONAL';

        $rowTotalOthersValue = $whiteRow;
        $rowTotalOthersValue[] = $totalOthersValue;
        $rowTotalOthersValue[16] = 'TOTAL OTROS VALORES RECIBIDOS';

        $data = array_map(function($datum) {
            return [
                $datum['ProfessionalDocument'],
                $datum['ProfessionalName'],
		$datum['PatientDocumentType'],
                $datum['PatientDocument'],
                $datum['PatientName'],
                $datum['EntityName'],
                $datum['AuthorizationNumber'],
                $datum['ServiceName'],
                $datum['PaymentProfessional'],
                $datum['Quantity'],
                $datum['CoPaymentAmount'],
                $datum['CoPaymentFrecuency'],
                $datum['TotalCopaymentReceived'],
                $datum['Pin'],
                $datum['KITMNB'],
                $datum['QuantityKITMNB'],
                $datum['OtherValuesReceived'],
                $datum['TotalCopaymentDelivered'],
                $datum['SubTotal']
            ];
        }, $data);
        $data[] = $rowTotalCopayment;
        $data[] = $rowTotalOthersValue;
        $data[] = $rowprofessionalTakenAmount;
        $data[] = $rowSubTotal;
        $excel = new ExcelBuilder($header, $data);
        $excel->build();
        return $excel->get();
        
        
        
        
    }

    public function getNominaReport(Request $request)
    {
        $initDate = Carbon::createFromFormat('d-m-Y', $request->input('InitDate'))->format('Y-m-d');
        $finalDate = Carbon::createFromFormat('d-m-Y', $request->input('FinalDate'))->format('Y-m-d');
        $professional = $request->input('ProfessionalId');
        $colecctionAccounts = CollectionAccount::select(\DB::raw('DISTINCT(sas.AssignServiceDetails.AssignServiceDetailId), sas.AssignServiceDetails.ProfessionalId, sas.CollectionAccounts.id, ProfessionalTakenAmount'))
            ->join('sas.VisitCollectionAcount', 'sas.VisitCollectionAcount.CollectionAccountId', '=', 'sas.CollectionAccounts.id')
            ->join('sas.AssignServiceDetails', function($join) use ($professional){
                $join = $join->on('sas.AssignServiceDetails.AssignServiceDetailId', '=', 'sas.VisitCollectionAcount.AssignServiceDetailId');
                if ($professional) {
                    $join->where('sas.AssignServiceDetails.ProfessionalId', $professional);
                }
            })
            ->whereBetween('sas.CollectionAccounts.RecordDate', [$initDate, $finalDate])
            ->get()->toArray();

        $detailsId = array_column($colecctionAccounts, 'AssignServiceDetailId');
        $data = new CopaymentController();
        $data = $data->getServices('', '', '', 1, 3, [],$detailsId);

        $collectionAccountId = [];
        $cant = count($colecctionAccounts);
        for ($i = 0; $i < $cant; $i++ ) {
            if (!in_array($colecctionAccounts[$i]['id'], $collectionAccountId) ) {
                $collectionAccountId[] = $colecctionAccounts[$i]['id'];
            } else {
                unset($colecctionAccounts[$i]);
            }
        }


        $cant = count($data);
        $professionalsId = [];
        $nominaData = [];
        $position = -1;
        for ($i = 0; $i < $cant;  $i++) {
            if (!in_array($data[$i]['ProfessionalId'], $professionalsId)) {
                $professionalsId[] = $data[$i]['ProfessionalId'];
                $nominaData[] = [
                    'professionalId' => $data[$i]['ProfessionalId'],
                    'professionalName' => $data[$i]['ProfessionalName'],
                    'professionalDocument' => $data[$i]['ProfessionalDocument'],
                    'subTotal' => $data[$i]['SubTotal']
                ];
                $position++;
            } else {
                $nominaData[$position]['subTotal'] += $data[$i]['SubTotal'];
            }
        }

        $nominaData = array_map(function($nominaDatum) use ($colecctionAccounts) {
            
            foreach ($colecctionAccounts as $collectionAccount) {
                if ($collectionAccount['ProfessionalId'] == $nominaDatum['professionalId']) {
                    $nominaDatum['subTotal'] -= $collectionAccount['ProfessionalTakenAmount'];
                }
            }
            $professional = Professional::with(['user:UserId,Email', 'specialty', 'accountType'])
                ->findOrFail($nominaDatum['professionalId'], ['ProfessionalId', 'AccountNumber', 'CodeBank', 'Address', 'Telephone1', 'SpecialtyId', 'AccountTypeId', 'UserId']);
            return [
                $nominaDatum['professionalName'],
                $nominaDatum['professionalDocument'],
                $professional->specialty->Name,
                $professional->AccountNumber,
                $professional->CodeBank,
                $professional->accountType->Name,
                $nominaDatum['subTotal'],
                $professional->Address,
                $professional->Telephone1,
                $professional->user->Email
            ];
        }, $nominaData);

        $header = [
            'NOMBES Y APELLIDOS',
            'CÉDULA',
            'CARGO',
            'NUMERO CUENTA',
            'BANCO',
            'CUENTA',
            'VALOR A PAGAR',
            'DIRECCION',
            'TELEFONO',
            'CORREO'
        ];

        $excel = new ExcelBuilder($header, $nominaData);
        $excel->build();
        return $excel->get();

    }

    public function getProfessionalReport(Request $request)
    {
	$state = $request->input('State');
	$professionals = Professional::select('cfg.Professionals.*');
	if ($state) {
	    $professionals->join('sec.Users', function ($join) use ($state) {
		$join->on('sec.Users.UserId', '=', 'cfg.Professionals.UserId')
		    ->where('sec.Users.State', $state == 2 ? 0 : 1);
	    });
	}
	
	if ($request->input('SpecialtyId')) {
	    $professionals->where('SpecialtyId', $request->input('SpecialtyId'));
	}

	if ($request->input('ContractTypeId')) {
	    $professionals->where('ContractTypeId', $request->input('ContractTypeId'));
	}

	$professionals = $professionals->with(['user', 'gender', 'specialty', 'contractType', 'accountType'])->get();

	$data = [];
	
	foreach ($professionals as $professional) {

	    $name = $professional->user->FirstName . ' ';
            if ($professional->user->SecondName) {
                $name .= $professional->user->SecondName . ' ';
            }
            $name .= $professional->user->Surname . ' ';
            if ($professional->user->SecondSurname) {
                $name .= $professional->user->SecondSurname;
            }
	    $data[] = [
		$name,
		$professional->Document,
		$professional->gender->Name,
		$professional->BirthDate ? Carbon::createFromFormat('Y-m-d', $professional->BirthDate)->format('d/m/Y') : '',
		$professional->specialty->Name,
		$professional->contractType ? $professional->contractType->Name : '',
		$professional->DateAdmission ? Carbon::createFromFormat('Y-m-d', substr($professional->DateAdmission, 0, 10))->format('d/m/Y') : '',
		$professional->CodeBank,
		$professional->accountType->Name,
		$professional->AccountNumber,
		$professional->Telephone1,
		$professional->Telephone2,
		$professional->user->Email,
		$professional->Address,
		$professional->Neighborhood,
		$professional->Availability,
		$professional->user->State ? 'ACTIVO' : 'INACTIVO',
	    ];
	}

	$header = [
	    'NOMBRE COMPLETO PROFESIONAL',
	    'NÚMERO DE CÉDULA',
	    'SEXO',
	    'FECHA DE NACIMIENTO',
	    'ESPECIALIDAD',
	    'TIPO DE CONTRATO',
	    'FECHA DE INGRESO',
	    'BANCO',
	    'TIPO DE CUENTA',
	    'NÚMERO DE CUENTA', 
	    'TELÉFONO',
	    'TELÉFONO SECUNDARIO',
	    'CORREO ELECTRÓNICO',
	    'DIRECCIÓN',
	    'BARRIO',
	    'DISPONIBILIDAD',
	    'ESTADO',
	];

	$excel = new ExcelBuilder($header, $data);
        $excel->build();
        return $excel->get();
    }

    public function getHoursWorkedReport(Request $request)
    {
        $request->validate([
            'InitDate' => 'required',
            'FinalDate' => 'required',
            'ReportType' => 'required'
        ]);

        $services = ServiceDetail::select('sas.AssignServiceDetails.*')
            ->join('sas.AssignService', 'sas.AssignServiceDetails.AssignServiceId', '=', 'sas.AssignService.AssignServiceId')
            ->join('cfg.Services', function ($join) {
                $join->on('sas.AssignService.ServiceId', '=', 'cfg.Services.ServiceId')
                    ->where('cfg.Services.ServiceTypeId', 2);
            });

        if ($request->input('ProfessionalId')) {
            $services->where('sas.AssignServiceDetails.ProfessionalId', $request->input('ProfessionalId'));
        }

        if ($request->input('StateId')) {
            $services->where('sas.AssignServiceDetails.StateId', $request->input('StateId'));
        }

        if ($request->input('ContractTypeId')) {
            $contractTypeId = $request->input('ContractTypeId');
            $services->join('cfg.Professionals', function ($join) use ($contractTypeId) {
               $join->on('sas.AssignServiceDetails.ProfessionalId', '=', 'cfg.Professionals.ProfessionalId')
                    ->where('cfg.Professionals.ContractTypeId', $contractTypeId);
            });
        }
        $initDate = Carbon::createFromFormat('d-m-Y', $request->input('InitDate'))->format('Y-m-d');
        $finalDate = Carbon::createFromFormat('d-m-Y', $request->input('FinalDate'))->format('Y-m-d');
        $services->whereBetween('sas.AssignServiceDetails.DateVisit', [$initDate, $finalDate])
            ->orderBy('sas.AssignServiceDetails.DateVisit', 'asc');
        $services = $services->get();

        $hours = [];
        $ranges = WorkScheduleRange::orderBy('WorkScheduleId')->get();
        if ($request->input('ReportType') == 1) {
            foreach ($services as $service) {
                if (!array_key_exists($service->ProfessionalId, $hours)) {
                    $hours[$service->ProfessionalId] = [
                        'basic' => [
                            'normal' => 0,
                            'holiday' => 0,
                            'breakTime' => 0,
                        ],
                        'extra_basic' => [
                            'normal' => 0,
                            'holiday' => 0
                        ],
                        'night' => [
                            'normal' => 0,
                            'holiday' => 0,
                            'breakTime' => 0,
                        ],
                        'extra_night' => [
                            'normal' => 0,
                            'holiday' => 0
                        ],
                        'workedDays' => [],
                        'attendedShifts' => 0
                    ];
                }
            }

            foreach ($services as $service) {
                $dataService = $service->service->service;
                $initTime = substr($dataService->InitTime, 0, 8);
                $breakTime = $dataService->BreakTime;
                $dateInit = $service->DateVisit;
                $final = Carbon::createFromFormat('Y-m-d H:i:s', $dateInit . ' ' .$initTime)->addHours($dataService->HoursToInvest);

                $finalDate = $final->format('Y-m-d');
                $initTime = substr($initTime, 0, 2);
                $hour = $initTime;
                for ($i = $initTime; $i < $initTime + $dataService->HoursToInvest; $i++) {
                    if ($i == 24) {
                        $hour = 0;
                    }
                    foreach ($ranges as $range) {
                        $initRange = (int) substr($range->Start, 0, 2);
                        $finalRange = (int) substr($range->End, 0, 2);
                        if (($hour >= $initRange && $hour < $finalRange) ||
                            ($hour >= $initRange && $hour > $finalRange && $initRange > $finalRange) ||
                            ($hour < $initRange && $hour < $finalRange && $initRange > $finalRange)) {
                            $finalDateIsHoliday = $this->isHoliday($finalDate);
                            $initDateIsHoliday = $this->isHoliday($dateInit);
                            if ($hour < $initTime) {
                                $isHoliday = $finalDateIsHoliday;
                            } else {
                                $isHoliday = $initDateIsHoliday;
                            }
                            if ($range->WorkScheduleId == 1) {
                                $workSchedule = 'basic';
                            } elseif ($range->WorkScheduleId == 2) {
                                $workSchedule = 'extra_basic';
                            } elseif ($range->WorkScheduleId == 3) {
                                $workSchedule = 'night';
                            } elseif ($range->WorkScheduleId == 4) {
                                $workSchedule = 'extra_night';
                            }

                            $day = $isHoliday ? 'holiday' : 'normal';
                            $hours[$service->ProfessionalId][$workSchedule][$day]++;
                            $hour++;
                            break;
                        }
                    }
                }

                $hours[$service->ProfessionalId]['attendedShifts']++;
                if (!in_array($dateInit, $hours[$service->ProfessionalId]['workedDays'])) {
                    $hours[$service->ProfessionalId]['workedDays'][] = $dateInit;
                }

                if (!in_array($finalDate, $hours[$service->ProfessionalId]['workedDays'])) {
                    $hours[$service->ProfessionalId]['workedDays'][] = $finalDate;
                }

                if ($dataService->BreakTime && $dataService->HoursToInvest >= 10) {
                    if ($dataService->HoursToInvest >= 10 && $dataService->HoursToInvest <= 12) {
                        if (!$initDateIsHoliday && !$finalDateIsHoliday) {
                            if ($hours[$service->ProfessionalId]['basic']['normal'] > $hours[$service->ProfessionalId]['night']['normal']) {
                                $hours[$service->ProfessionalId]['basic']['normal'] -= $dataService->BreakTime;
                                $hours[$service->ProfessionalId]['basic']['breakTime'] += $dataService->BreakTime;
                            } else {
                                $hours[$service->ProfessionalId]['night']['normal'] -= $dataService->BreakTime;
                                $hours[$service->ProfessionalId]['night']['breakTime'] += $dataService->BreakTime;
                            }
                        } else if ($initDateIsHoliday && $finalDateIsHoliday && $dateInit == $finalDate) {
                            $hours[$service->ProfessionalId]['basic']['holiday'] -= $dataService->BreakTime;
                            $hours[$service->ProfessionalId]['basic']['breakTime'] += $dataService->BreakTime;
                        } else if (!$initDateIsHoliday && $finalDateIsHoliday) {
                                if ($breakTime > 1) {
                                    $hours[$service->ProfessionalId]['night']['normal'] -= 1;
                                    $hours[$service->ProfessionalId]['night']['holiday'] -= 1;
                                    $hours[$service->ProfessionalId]['night']['breakTime'] += 2;
                                } else {
                                    $hours[$service->ProfessionalId]['night']['normal'] -= 1;
                                    $hours[$service->ProfessionalId]['night']['breakTime'] += 1;
                                }

                        } else if (($initDateIsHoliday && !$finalDateIsHoliday) || ($initDateIsHoliday && $finalDateIsHoliday && $dateInit != $finalDate)) {
                            if ($breakTime > 1) {
                                if ($hours[$service->ProfessionalId]['basic']['holiday']) {
                                    $hours[$service->ProfessionalId]['basic']['holiday'] -= 1;
                                    $hours[$service->ProfessionalId]['basic']['breakTime'] += 1;
                                    $hours[$service->ProfessionalId]['night']['holiday'] -= 1;
                                    $hours[$service->ProfessionalId]['night']['breakTime'] += 1;
                                } else {
                                    $hours[$service->ProfessionalId]['night']['holiday'] -= 2;
                                    $hours[$service->ProfessionalId]['night']['breakTime'] += 2;
                                }
                            } else {
                                $hours[$service->ProfessionalId]['night']['holiday'] -= 1;
                                $hours[$service->ProfessionalId]['night']['breakTime'] += 1;
                            }
                        }
                    } elseif ($dataService->HoursToInvest == 24) {
                        if (!$initDateIsHoliday && !$finalDateIsHoliday) {
                            $hours[$service->ProfessionalId]['basic']['normal'] -= 2;
                            $hours[$service->ProfessionalId]['night']['normal'] -= 2;
                            $hours[$service->ProfessionalId]['basic']['breakTime'] += 2;
                            $hours[$service->ProfessionalId]['night']['breakTime'] += 2;
                        } elseif (!$initDateIsHoliday && $finalDateIsHoliday) {
                            $hours[$service->ProfessionalId]['basic']['holiday'] -= 2;
                            $hours[$service->ProfessionalId]['night']['normal'] -= 2;
                            $hours[$service->ProfessionalId]['basic']['breakTime'] += 2;
                            $hours[$service->ProfessionalId]['night']['breakTime'] += 2;
                        } elseif ($initDateIsHoliday && $finalDateIsHoliday) {
                            $hours[$service->ProfessionalId]['basic']['holiday'] -= 2;
                            $hours[$service->ProfessionalId]['night']['holiday'] -= 2;
                            $hours[$service->ProfessionalId]['basic']['breakTime'] += 2;
                            $hours[$service->ProfessionalId]['night']['breakTime'] += 2;
                        } elseif ($initDateIsHoliday && !$finalDateIsHoliday) {
                            $hours[$service->ProfessionalId]['basic']['normal'] -= 2;
                            $hours[$service->ProfessionalId]['night']['holiday'] -= 2;
                            $hours[$service->ProfessionalId]['basic']['breakTime'] += 2;
                            $hours[$service->ProfessionalId]['night']['breakTime'] += 2;
                        }
                    }
                }
            }

            $header = [
                'DOC. PROF.',
                'NOMBRE DEL PROFESIONAL',
                'ESPECIALIDAD',
                'FECHA INGRESO',
                'TIPO CONTRATO',
                'DIAS TRABAJADOS',
                'TURNOS ATENDIDOS',
                'H. BÁSICA',
                'H.E. DIA',
                'H.E. NOC',
                'H.E. DIA FEST.',
                'H.E. NOC FEST',
                'TOTAL H.E',
                'TOTAL HORAS',
                'H. RECARGO NOC',
                'H. RECARGO DIA FEST',
                'H. RECARGO NOC FEST',
                'TOTAL RECARGOS',
                'H. DESCANSO'
            ];
            $data = [];
            foreach ($hours as $key => $value) {
                $professional = Professional::findOrFail($key);

                $name = $professional->user->FirstName . ' ';
                if ($professional->user->SecondName) {
                    $name .= $professional->user->SecondName . ' ';
                }
                $name .= $professional->user->Surname . ' ';
                if ($professional->user->SecondSurname) {
                    $name .= $professional->user->SecondSurname;
                }
                $totalExtraHours = $value['extra_basic']['normal'] + $value['extra_night']['normal'] +$value['extra_basic']['holiday'] + $value['extra_night']['holiday'];
                $totalRecargos = $value['night']['normal'] + $value['basic']['holiday'] + $value['night']['holiday'];
                $data[] = [
                    $professional->Document,
                    $name,
                    $professional->specialty->Name,
                    $professional->DateAdmission ? Carbon::createFromFormat('Y-m-d', substr($professional->DateAdmission, 0, 10))->format('d-m-Y') : '',
                    $professional->contractType ? $professional->contractType->Name : '',
                    count($value['workedDays']),
                    $value['attendedShifts'],
                    $value['basic']['normal'],
                    $value['extra_basic']['normal'],
                    $value['extra_night']['normal'],
                    $value['extra_basic']['holiday'],
                    $value['extra_night']['holiday'],
                    $totalExtraHours,
                    $value['basic']['normal'] + $totalExtraHours,
                    $value['night']['normal'],
                    $value['basic']['holiday'],
                    $value['night']['holiday'],
                    $totalRecargos,
                    $value['basic']['breakTime'] + $value['night']['breakTime']
                ];
            }

            $excel = new ExcelBuilder($header, $data);
            $excel->build();
            return $excel->get();

        } else {
            foreach ($services as $service) {
                if (!array_key_exists($service->AssignServiceDetailId, $hours)) {
                    $hours[$service->AssignServiceDetailId] = [
                        'basic' => [
                            'normal' => 0,
                            'holiday' => 0,
                            'breakTime' => 0,
                        ],
                        'extra_basic' => [
                            'normal' => 0,
                            'holiday' => 0
                        ],
                        'night' => [
                            'normal' => 0,
                            'holiday' => 0,
                            'breakTime' => 0,
                        ],
                        'extra_night' => [
                            'normal' => 0,
                            'holiday' => 0
                        ]
                    ];
                }
            }

            foreach ($services as $service) {
                $dataService = $service->service->service;
                $initTime = substr($dataService->InitTime, 0, 8);
                $breakTime = $dataService->BreakTime;
                $dateInit = $service->DateVisit;
                $final = Carbon::createFromFormat('Y-m-d H:i:s', $dateInit . ' ' .$initTime)->addHours($dataService->HoursToInvest);

                $finalDate = $final->format('Y-m-d');
                $initTime = substr($initTime, 0, 2);
                $hour = $initTime;
                for ($i = $initTime; $i < $initTime + $dataService->HoursToInvest; $i++) {
                    if ($i == 24) {
                        $hour = 0;
                    }
                    foreach ($ranges as $range) {
                        $initRange = (int) substr($range->Start, 0, 2);
                        $finalRange = (int) substr($range->End, 0, 2);
                        if (($hour >= $initRange && $hour < $finalRange) ||
                            ($hour >= $initRange && $hour > $finalRange && $initRange > $finalRange) ||
                            ($hour < $initRange && $hour < $finalRange && $initRange > $finalRange)) {
                            $finalDateIsHoliday = $this->isHoliday($finalDate);
                            $initDateIsHoliday = $this->isHoliday($dateInit);
                            if ($hour < $initTime) {
                                $isHoliday = $finalDateIsHoliday;
                            } else {
                                $isHoliday = $initDateIsHoliday;
                            }
                            if ($range->WorkScheduleId == 1) {
                                $workSchedule = 'basic';
                            } elseif ($range->WorkScheduleId == 2) {
                                $workSchedule = 'extra_basic';
                            } elseif ($range->WorkScheduleId == 3) {
                                $workSchedule = 'night';
                            } elseif ($range->WorkScheduleId == 4) {
                                $workSchedule = 'extra_night';
                            }

                            $day = $isHoliday ? 'holiday' : 'normal';
                            $hours[$service->AssignServiceDetailId][$workSchedule][$day]++;
                            $hour++;
                            break;
                        }
                    }
                }

                if ($dataService->BreakTime && $dataService->HoursToInvest >= 10) {
                    if ($dataService->HoursToInvest >= 10 && $dataService->HoursToInvest <= 12) {
                        if (!$initDateIsHoliday && !$finalDateIsHoliday) {
                            if ($hours[$service->AssignServiceDetailId]['basic']['normal'] > $hours[$service->AssignServiceDetailId]['night']['normal']) {
                                $hours[$service->AssignServiceDetailId]['basic']['normal'] -= $dataService->BreakTime;
                                $hours[$service->AssignServiceDetailId]['basic']['breakTime'] += $dataService->BreakTime;
                            } else {
                                $hours[$service->AssignServiceDetailId]['night']['normal'] -= $dataService->BreakTime;
                                $hours[$service->AssignServiceDetailId]['night']['breakTime'] += $dataService->BreakTime;
                            }
                        } else if ($initDateIsHoliday && $finalDateIsHoliday && $dateInit == $finalDate) {
                            $hours[$service->AssignServiceDetailId]['basic']['holiday'] -= $dataService->BreakTime;
                            $hours[$service->AssignServiceDetailId]['basic']['breakTime'] += $dataService->BreakTime;
                        } else if (!$initDateIsHoliday && $finalDateIsHoliday) {
                            if ($breakTime > 1) {
                                $hours[$service->AssignServiceDetailId]['night']['normal'] -= 1;
                                $hours[$service->AssignServiceDetailId]['night']['holiday'] -= 1;
                                $hours[$service->AssignServiceDetailId]['night']['breakTime'] += 2;
                            } else {
                                $hours[$service->AssignServiceDetailId]['night']['normal'] -= 1;
                                $hours[$service->AssignServiceDetailId]['night']['breakTime'] += 1;
                            }

                        } else if (($initDateIsHoliday && !$finalDateIsHoliday) || ($initDateIsHoliday && $finalDateIsHoliday && $dateInit != $finalDate)) {
                            if ($breakTime > 1) {
                                if ($hours[$service->AssignServiceDetailId]['basic']['holiday']) {
                                    $hours[$service->AssignServiceDetailId]['basic']['holiday'] -= 1;
                                    $hours[$service->AssignServiceDetailId]['basic']['breakTime'] += 1;
                                    $hours[$service->AssignServiceDetailId]['night']['holiday'] -= 1;
                                    $hours[$service->AssignServiceDetailId]['night']['breakTime'] += 1;
                                } else {
                                    $hours[$service->AssignServiceDetailId]['night']['holiday'] -= 2;
                                    $hours[$service->AssignServiceDetailId]['night']['breakTime'] += 2;
                                }
                            } else {
                                $hours[$service->AssignServiceDetailId]['night']['holiday'] -= 1;
                                $hours[$service->AssignServiceDetailId]['night']['breakTime'] += 1;
                            }
                        }
                    } elseif ($dataService->HoursToInvest == 24) {
                        if (!$initDateIsHoliday && !$finalDateIsHoliday) {
                            $hours[$service->AssignServiceDetailId]['basic']['normal'] -= 2;
                            $hours[$service->AssignServiceDetailId]['night']['normal'] -= 2;
                            $hours[$service->AssignServiceDetailId]['basic']['breakTime'] += 2;
                            $hours[$service->AssignServiceDetailId]['night']['breakTime'] += 2;
                        } elseif (!$initDateIsHoliday && $finalDateIsHoliday) {
                            $hours[$service->AssignServiceDetailId]['basic']['holiday'] -= 2;
                            $hours[$service->AssignServiceDetailId]['night']['normal'] -= 2;
                            $hours[$service->AssignServiceDetailId]['basic']['breakTime'] += 2;
                            $hours[$service->AssignServiceDetailId]['night']['breakTime'] += 2;
                        } elseif ($initDateIsHoliday && $finalDateIsHoliday) {
                            $hours[$service->AssignServiceDetailId]['basic']['holiday'] -= 2;
                            $hours[$service->AssignServiceDetailId]['night']['holiday'] -= 2;
                            $hours[$service->AssignServiceDetailId]['basic']['breakTime'] += 2;
                            $hours[$service->AssignServiceDetailId]['night']['breakTime'] += 2;
                        } elseif ($initDateIsHoliday && !$finalDateIsHoliday) {
                            $hours[$service->AssignServiceDetailId]['basic']['normal'] -= 2;
                            $hours[$service->AssignServiceDetailId]['night']['holiday'] -= 2;
                            $hours[$service->AssignServiceDetailId]['basic']['breakTime'] += 2;
                            $hours[$service->AssignServiceDetailId]['night']['breakTime'] += 2;
                        }
                    }
                }
            }

            $header = [
                'DOC. PROF.',
                'NOMBRE DEL PROFESIONAL',
                'ESPECIALIDAD',
                'FECHA INGRESO',
                'TIPO CONTRATO',
                'NOMBRE DEL PACIENTE',
                'TIPO DOCUMENTO',
                'NÚMERO DOCUMENTO',
                'FECHA ATENCIÓN',
                'SERVICIO',
                'ESTADO',
                'TIPO DÍA 1',
                'TIPO DÍA 2',
                'H. BÁSICA',
                'H.E. DIA',
                'H.E. NOC',
                'H.E. DIA FEST.',
                'H.E. NOC FEST',
                'TOTAL H.E',
                'TOTAL HORAS',
                'H. RECARGO NOC',
                'H. RECARGO DIA FEST',
                'H. RECARGO NOC FEST',
                'TOTAL RECARGOS',
                'H. DESCANSO',
                'FECHA VERIFICADO',
                'VERIFICADO POR'
            ];

            $data = [];
            foreach ($hours as $key => $value) {
                $service = ServiceDetail::with(['professional', 'service', 'state'])->findOrFail($key);
                $professional = $service->professional;
                $patient = $service->service->patient;
                $name = $professional->user->FirstName . ' ';
                if ($professional->user->SecondName) {
                    $name .= $professional->user->SecondName . ' ';
                }
                $name .= $professional->user->Surname . ' ';
                if ($professional->user->SecondSurname) {
                    $name .= $professional->user->SecondSurname;
                }

                $dataService = $service->service->service;
                $initTime = substr($dataService->InitTime, 0, 8);
                $dateInit = $service->DateVisit;
                $final = Carbon::createFromFormat('Y-m-d H:i:s', $dateInit . ' ' .$initTime)->addHours($dataService->HoursToInvest);
                $finalDate = $final->format('Y-m-d');

                $initDateIsHoliday = $this->isHoliday($dateInit) ? 'FESTIVO' : 'HÁBIL';
                $finalDateIsHoliday = '';
                if ($dateInit != $finalDate) {
                    $finalDateIsHoliday = $this->isHoliday($finalDate) ? 'FESTIVO' : 'HÁBIL';
                }


                $totalExtraHours = $value['extra_basic']['normal'] +
                    $value['extra_night']['normal'] +
                    $value['extra_basic']['holiday'] +
                    $value['extra_night']['holiday'];

                $totalHours = $value['basic']['normal'] + $totalExtraHours;
                $totalRecargos = $value['night']['normal'] +
                    $value['basic']['holiday'] +
                    $value['night']['holiday'];

                $nameUser = '';
                if ($service->VerifiedBy) {
                    $user = User::findOrFail($service->VerifiedBy);
                    $nameUser = $user->FirstName . ' ';
                    if ($user->SecondName) {
                        $nameUser .= $user->SecondName . ' ';
                    }
                    $nameUser .= $user->Surname . ' ';
                    if ($user->SecondSurname) {
                        $nameUser .= $user->SecondSurname;
                    }
                }

                $data[] = [
                    $professional->Document,
                    $name,
                    $professional->specialty->Name,
                    $professional->DateAdmission ? Carbon::createFromFormat('Y-m-d', substr($professional->DateAdmission, 0, 10))->format('d-m-Y') : '',
                    $professional->contractType ? $professional->contractType->Name : '',
                    $patient->NameCompleted,
                    $patient->documentType->Name,
                    $patient->Document,
                    Carbon::createFromFormat('Y-m-d', $dateInit)->format('d/m/Y'),
                    $dataService->Name,
                    $service->state->Name,
                    $initDateIsHoliday,
                    $finalDateIsHoliday,
                    $value['basic']['normal'],
                    $value['extra_basic']['normal'],
                    $value['extra_night']['normal'],
                    $value['extra_basic']['holiday'],
                    $value['extra_night']['holiday'],
                    $totalExtraHours,
                    $totalHours,
                    $value['night']['normal'],
                    $value['basic']['holiday'],
                    $value['night']['holiday'],
                    $totalRecargos,
                    $value['basic']['breakTime'] + $value['night']['breakTime'],
                    $service->VerificationDate ? Carbon::createFromFormat('Y-m-d', substr($service->VerificationDate, 0, 9))->format('d/m/Y') : '',
                    $nameUser
                ];
            }

            $excel = new ExcelBuilder($header, $data);
            $excel->build();
            return $excel->get();
        }


    }

    private function isHoliday($date)
    {
        $isHoliday = \DB::select("select dbo.F_CALCULA_ES_FESTIVO('$date') as isHoliday")[0]->isHoliday;
        if (!$isHoliday) {
            $dayWeek = Carbon::createFromFormat('Y-m-d', $date)->format('N');
            $isHoliday = $dayWeek == 7 ? 1 : 0;
        }
        return $isHoliday;
    }
}
