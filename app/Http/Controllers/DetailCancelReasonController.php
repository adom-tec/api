<?php

namespace App\Http\Controllers;

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
        return response()->json([
            'message' => 'Razones asociadas exitosamente '
        ], 200);
    }
}
