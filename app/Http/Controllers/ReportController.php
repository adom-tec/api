<?php

namespace App\Http\Controllers;

use App\Professional;
use Illuminate\Http\Request;

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
		            ,CASE Ags.CopaymentStatus WHEN 0 THEN 'SIN ENTREGADO' ELSE 'ENTREGADO' END CopaymentStatus
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
            INNER JOIN sec.Users usr ON usr.UserId = Ags.AssignedBy";

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

        if ($request->input('ServiceId')) {
            $sql .= " AND Ags.ServiceId = " . $request->input('ServiceId');
        }

        $data = \DB::select(\DB::raw($sql));

        $maxCountProfessionals = 0;

        foreach ($data as $datum) {
            $serviceId = $datum->AssignServiceId;
            $professionals = Professional::select(\DB::raw('DISTINCT(cfg.Professionals.ProfessionalId), Document, UserId'))
                ->join('sas.AssignServiceDetails', function ($join) use ($serviceId) {
                   $join->on('cfg.Professionals.ProfessionalId', '=', 'sas.AssignServiceDetails.ProfessionalId')
                        ->where('sas.AssignServiceDetails.AssignServiceId', $serviceId);
                })->with('user')
                ->get()
                ->map(function ($professional) use ($maxCountProfessionals) {
                    $name = $professional->user->FirstName . ' ';
                    if ($professional->user->SecondName) {
                        $name .= $professional->user->SecondName . ' ';
                    }
                    $name .= $professional->user->Surname . ' ';
                    if ($professional->user->SecondSurname) {
                        $name .= $professional->user->SecondSurname;
                    }
                    return [
                        'Document' => $professional->Document,
                        'Name' => $name
                    ];
                });
                $maxCountProfessionals = count($professionals) > $maxCountProfessionals ? count($professionals) : $maxCountProfessionals;
                $datum->professionals = $professionals;
        }

        return $data;
    }
}
