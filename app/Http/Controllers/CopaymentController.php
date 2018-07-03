<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PatientService;

class CopaymentController extends Controller
{
    public function index(Request $request) {
        $request->validate([
            'ProfessionalId' => 'required|exists:sqlsrv.cfg.Professionals,ProfessionalId',
            'InitDate' => 'required',
            'FinalDate' => 'required',
            'StateId' => 'required',
            'CopaymentState' => 'required'
        ]);
        $professionalId = $request->input('ProfessionalId');
        $initDate = $request->input('InitDate');
        $finalDate = $request->input('FinalDate');
        $copaymentStatus = $request->input('CopaymentState');
        $assignServices = PatientService::select('sas.AssignService.*', 'sas.AssignServiceDetails.Pin', 'sas.AssignServiceDetails.ReceivedAmount')->
            join('sas.AssignServiceDetails', function ($join) use ($professionalId, $initDate, $finalDate, $copaymentStatus) {
                $join->on('sas.AssignServiceDetails.AssignServiceId', '=', 'sas.AssignService.AssignServiceId')
                    ->where('sas.AssignServiceDetails.ProfessionalId', $professionalId)
                    ->whereBetween('DateVisit', [$initDate, $finalDate])
                    ->where('delivered', $copaymentStatus);
            })->orderBy('sas.AssignService.AssignServiceId')
            ->with(['patient', 'entity', 'service', 'coPaymentFrecuency', 'supplies']);

        if (array_search($request->input('StateId'), [1,2])) {
            $assignServices->where('StatusId', $request->input('StateId'));
        }

        return $assignServices->get();



    }
}
