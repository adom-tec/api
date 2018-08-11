<?php

namespace App\Http\Controllers;

use App\CancelReason;
use App\Mail\CancelVisits;
use App\Professional;
use Illuminate\Http\Request;
use App\ServiceDetail;
use App\DetailCancelReason;

class DetailCancelReasonController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'reasons.*.CancelReasonId' => 'required|exists:sqlsrv.sas.CancelReasons,id',
            'reasons.*.AssignServiceDetailId' => 'required:exists:sqlsrv.sas.AssignServiceDetails,AssignServiceDetailsId'
        ]);
        $reasons = $request->input('reasons');
        foreach($reasons as $reason ) {
            DetailCancelReason::where('AssignServiceDetailId', $reason['AssignServiceDetailId'])
                ->delete();
            $detailCancelReason = new DetailCancelReason();
            $detailCancelReason->AssignServiceDetailId = $reason['AssignServiceDetailId'];
            $detailCancelReason->CancelReasonId = $reason['CancelReasonId'];
            $detailCancelReason->save();

        }

        $cantVisits = count($request->input('reasons'));
        $cancelReason = CancelReason::findOrFail($reason['CancelReasonId'])->name;
        $service = ServiceDetail::findOrFail($reason['AssignServiceDetailId'])->service;

        if ($service->ProfessionalId != -1) {
            $professional = Professional::findOrFail($service->ProfessionalId);
            $name = $professional->user->FirstName . ' ';
            if ($professional->user->SecondName) {
                $name .= $professional->user->SecondName . ' ';
            }
            $name .= $professional->user->Surname . ' ';
            if ($professional->user->SecondSurname) {
                $name .= $professional->user->SecondSurname;
            }

            $serviceName = $service->service->Name;
            $patient = $service->patient;
            $docPatient = $patient->Document;
            $patientName = $patient->NameCompleted;

            \Mail::to($professional->user->Email)->send(new CancelVisits($name, $docPatient, $patientName, $serviceName, $cantVisits, $cancelReason));

        }

        return response()->json([
            'message' => 'Razones asociadas exitosamente '
        ], 200);
    }
}
