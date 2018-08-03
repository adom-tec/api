<?php

namespace App\Http\Controllers;

use App\Professional;
use Illuminate\Http\Request;
use App\ExcelBuilder;
use App\DetailAnswer;
use App\CollectionAccount;
use App\Http\Controllers\CopaymentController;
use Carbon\Carbon;

class ReportController extends Controller
{
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
            $sql .= " AND Ags.InitialDate >= CONVERT(DATE, '$initDate', 105)
             AND Ags.StateId = 2
             AND EXISTS(SELECT 1 FROM sas.AssignServiceDetails
               WHERE DateVisit >= CONVERT(DATE, '$initDate', 105))
             AND EXISTS(SELECT 1 FROM sas.AssignServiceDetails 
               WHERE RecordDate >= CONVERT(DATE, '$initDate', 105))";
        }

        if ($request->input('FinalDate')) {
            $finalDate = $request->input('FinalDate');
            $sql .= " AND Ags.InitialDate <= CONVERT(DATE, '$finalDate', 105)
              AND EXISTS(SELECT 1 FROM sas.AssignServiceDetails 
                WHERE DateVisit <= CONVERT(DATE, '$finalDate',105)) 
              AND EXISTS(SELECT 1 FROM sas .AssignServiceDetails 
                WHERE RecordDate <= CONVERT(DATE, '$finalDate',105))";
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

        $sql .= " ORDER BY ags.RecordDate";

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

        return response($excel->get())->header('Access-Control-Allow-Origin','http://192.168.0.13:4200');
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
        WHERE 1 = 1";

        if ($request->input('InitDate')) {
            $initDate = $request->input('InitDate');
            $sql .= " AND Ags.InitialDate >= CONVERT(DATE, '$initDate', 105)
            AND Ags.StateId = 2
            AND EXISTS(SELECT 1 FROM sas.AssignServiceDetails
            WHERE DateVisit >= CONVERT(DATE, '$initDate', 105))
            AND EXISTS(SELECT 1 FROM sas.AssignServiceDetails 
            WHERE RecordDate >= CONVERT(DATE, '$initDate', 105))";
        }

        if ($request->input('FinalDate')) {
            $finalDate = $request->input('FinalDate');
            $sql .= " AND Ags.InitialDate <= CONVERT(DATE, '$finalDate', 105)
            AND EXISTS(SELECT 1 FROM sas.AssignServiceDetails 
                WHERE DateVisit <= CONVERT(DATE, '$finalDate',105)) 
            AND EXISTS(SELECT 1 FROM sas .AssignServiceDetails 
                WHERE RecordDate <= CONVERT(DATE, '$finalDate',105))";
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

        $sql .= " ORDER BY Asd.DateVisit";

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
            $sql .= " AND Ags.InitialDate >= CONVERT(DATETIME, '$initDate', 105) ";
        }

        if ($request->input('FinalDate')) {
            $finalDate = $request->input('FinalDate');
            $sql .= " AND Ags.InitialDate <= CONVERT(DATETIME, '$finalDate', 105) ";
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



        $sql .= " ORDER BY Asd.AssignServiceDetailId DESC";

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
        $colecctionAccounts = CollectionAccount::select(\DB::raw('DISTINCT(sas.AssignServiceDetails.AssignServiceDetailId), sas.CollectionAccounts.*'))
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
            'N° DOCUMENTO DE IDENTIDAD',
            'NOMBRE PROFESIONAL',
            'N° DOCUMENTO  DEL  PACIENTE',
            'NOMBRE COMPLETO DEL PACIENTE',
            'ENTIDAD',
            'AUTORIZACIÓN (Registre la autorización. Para particulares y Javesalud registre 0)',
            'TIPO DE TERAPIA',
            'VALOR A PAGAR AL PROFESIONAL POR TERAPIA',
            'CANTIDAD DE TERAPIAS REALIZADAS',
            'COPAGO(Registre el valor del copago realizado por el paciente.Si no aplica registrar 0)',
            'FRECUENCIA COPAGO(Selecciones la periodicidad con que el paciente realiza el copago)',
            'VALOR TOTAL RECAUDO COPAGOS(Registre el valor total recibido en copagos.Si no recibe copagos, registre 0)',
            'VALE/PIN(Registrar 0 en caso de no recibir Vale o Pin)',
            'TIPO DE DOCUMENTO DEL PACIENTE',
            'KIT MNB',
            'CUANTOS KIT UTILIZO',
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
        $data = array_map(function($datum) {
            return [
                $datum['ProfessionalDocument'],
                $datum['ProfessionalName'],
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
                $datum['PatientDocumentType'],
                $datum['KITMNB'],
                $datum['QuantityKITMNB'],
                $datum['TotalCopaymentDelivered'],
                $datum['SubTotal']
            ];
        }, $data);
        $data[] = $rowTotalCopayment;
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
        $colecctionAccounts = CollectionAccount::select(\DB::raw('DISTINCT(sas.AssignServiceDetails.AssignServiceDetailId), sas.AssignServiceDetails.ProfessionalId, sas.CollectionAccounts.*'))
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
}
